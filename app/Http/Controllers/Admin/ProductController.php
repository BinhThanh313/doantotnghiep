<?php
namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * POST /api/admin/products/import
     * Nhập sản phẩm từ file Excel/CSV.
     * - Nếu sản phẩm đã tồn tại (so khớp theo tên, không phân biệt hoa thường)
     *   => cộng dồn số lượng vào tồn kho hiện tại + ghi inventory log.
     * - Nếu chưa có => tạo sản phẩm mới.
     *
     * Cột nhận diện (không phân biệt hoa thường, có dấu/không dấu đều OK):
     *   name | tên sản phẩm
     *   category | danh mục
     *   price | giá bán
     *   original_price | giá gốc
     *   stock | tồn kho | số lượng
     *   description | mô tả
     *   image | ảnh chính        (URL ảnh đại diện sản phẩm)
     *   image_2..image_6 | anh_2..anh_6   (URL các ảnh phụ, tối đa 6 ảnh/dòng)
     *
     * Ảnh được tải về và lưu vào storage/app/public/products, KHÔNG dùng
     * ảnh placeholder sinh tự động (SVG) — chỉ dùng URL ảnh thật do người
     * nhập cung cấp. Nếu 1 URL tải lỗi (mạng, 404...) thì dòng đó vẫn được
     * tạo/cộng dồn bình thường, chỉ riêng ảnh đó bị bỏ qua và ghi vào errors.
     *
     * Field "replace_images" (gửi kèm ngoài file, không phải cột trong Excel,
     * ví dụ formData.append('replace_images', '1')): nếu = true, với các
     * sản phẩm ĐÃ TỒN TẠI có URL ảnh mới trong file, hệ thống sẽ XOÁ hết
     * ảnh cũ (kể cả ảnh placeholder) trước khi gắn ảnh mới. Dùng để "sửa
     * lại ảnh" cho catalog sản phẩm hiện có, không chỉ để nhập hàng mới.
     */
    public function import(Request $request)
    {
        // File Excel có thể có hàng trăm ảnh (mỗi sản phẩm tối đa 6 ảnh) phải
        // tải tuần tự về server trong đúng 1 request này. Với max_execution_time
        // mặc định (30-60s) rất dễ bị PHP kill giữa chừng, khiến trình duyệt
        // nhận về response rỗng/mất kết nối ("Failed to load response data"
        // trong DevTools) dù logic import hoàn toàn không lỗi.
        // set_time_limit(0) = không giới hạn thời gian thực thi cho riêng
        // request này (không ảnh hưởng các request khác).
        set_time_limit(0);

        $request->validate([
            'file'           => 'required|file|mimes:xlsx,xls,csv',
            'replace_images' => 'nullable|boolean',
        ]);

        // Khi bật: với các sản phẩm ĐÃ TỒN TẠI trong file, xoá toàn bộ ảnh
        // cũ (kể cả ảnh placeholder SVG do lệnh products:generate-placeholder-images
        // sinh ra trước đây) rồi thay bằng ảnh thật lấy từ URL trong file.
        // Dùng để "sửa ảnh" cho sản phẩm đang có sẵn trong hệ thống, không
        // chỉ dùng để nhập sản phẩm mới.
        $replaceImages = $request->boolean('replace_images');

        $path = $request->file('file')->getRealPath();

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Không đọc được file: ' . $e->getMessage()], 422);
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (empty($rows)) {
            return response()->json(['message' => 'File rỗng'], 422);
        }

        // Chuẩn hoá header
        $rawHeader = array_shift($rows);
        $aliasMap = [
            'name' => 'name', 'ten' => 'name', 'tensanpham' => 'name', 'tensp' => 'name',
            'category' => 'category', 'danhmuc' => 'category',
            'price' => 'price', 'gia' => 'price', 'giaban' => 'price',
            'originalprice' => 'original_price', 'giagoc' => 'original_price',
            'stock' => 'stock', 'tonkho' => 'stock', 'soluong' => 'stock',
            'description' => 'description', 'mota' => 'description',
            'image' => 'image', 'anh' => 'image', 'anhchinh' => 'image', 'hinhanh' => 'image',
            'image2' => 'image_2', 'anh2' => 'image_2',
            'image3' => 'image_3', 'anh3' => 'image_3',
            'image4' => 'image_4', 'anh4' => 'image_4',
            'image5' => 'image_5', 'anh5' => 'image_5',
            'image6' => 'image_6', 'anh6' => 'image_6',
        ];

        $header = [];
        foreach ($rawHeader as $col) {
            $key = \Illuminate\Support\Str::slug((string) $col, ''); // bỏ dấu, khoảng trắng
            $header[] = $aliasMap[$key] ?? $key;
        }

        $created = 0;
        $updated = 0;
        $errors  = [];
        $userId  = $request->user()?->id;

        DB::transaction(function () use ($rows, $header, &$created, &$updated, &$errors, $userId, $replaceImages) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +1 cho header, +1 vì index từ 0
                $data = array_combine($header, array_pad($row, count($header), null));

                $name = trim((string) ($data['name'] ?? ''));
                if ($name === '') {
                    continue; // dòng trống bỏ qua
                }

                $stock = (int) ($data['stock'] ?? 0);

                $product = Product::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

                if ($product) {
                    // ===== Sản phẩm đã tồn tại => cộng dồn tồn kho =====
                    $before = $product->stock;
                    $after  = $before + $stock;

                    $product->update(['stock' => $after]);

                    if ($stock !== 0) {
                        InventoryLog::create([
                            'product_id'      => $product->id,
                            'variant_id'      => null,
                            'quantity_change' => $stock,
                            'stock_before'    => $before,
                            'stock_after'     => $after,
                            'reason'          => 'restock',
                            'reference_type'  => 'import',
                            'notes'           => 'Nhập từ file Excel',
                            'created_by'      => $userId,
                        ]);
                    }

                    // Cập nhật giá nếu file có cung cấp
                    if (!empty($data['price'])) {
                        $product->price = (float) $data['price'];
                    }
                    if (array_key_exists('original_price', $data) && $data['original_price'] !== null && $data['original_price'] !== '') {
                        $product->original_price = (float) $data['original_price'];
                    }
                    $product->save();

                    // Ảnh thật (nếu file có cung cấp URL): mặc định thêm bổ
                    // sung vào gallery hiện có; nếu bật "replace_images" thì
                    // xoá hết ảnh cũ (kể cả placeholder) rồi thay bằng ảnh mới.
                    $this->importImagesForProduct($product, $data, $rowNumber, $errors, $replaceImages);

                    $updated++;
                } else {
                    // ===== Sản phẩm mới =====
                    $categoryName = trim((string) ($data['category'] ?? ''));
                    $category = $categoryName !== ''
                        ? Category::whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])->first()
                        : null;

                    if (!$category) {
                        $errors[] = "Dòng {$rowNumber}: Không tìm thấy danh mục '{$categoryName}' cho sản phẩm '{$name}', đã bỏ qua.";
                        continue;
                    }

                    $product = Product::create([
                        'category_id'    => $category->id,
                        'name'           => $name,
                        'slug'           => \Illuminate\Support\Str::slug($name) . '-' . \Illuminate\Support\Str::random(6),
                        'description'    => $data['description'] ?? null,
                        'price'          => (float) ($data['price'] ?? 0),
                        'original_price' => !empty($data['original_price']) ? (float) $data['original_price'] : null,
                        'stock'          => $stock,
                        'is_active'      => true,
                        'is_new'         => true,
                    ]);

                    if ($stock > 0) {
                        InventoryLog::create([
                            'product_id'      => $product->id,
                            'variant_id'      => null,
                            'quantity_change' => $stock,
                            'stock_before'    => 0,
                            'stock_after'     => $stock,
                            'reason'          => 'restock',
                            'reference_type'  => 'import',
                            'notes'           => 'Tạo mới từ file Excel',
                            'created_by'      => $userId,
                        ]);
                    }

                    $this->generateDefaultSpecifications($product);

                    // Ảnh thật: ảnh đầu tiên (cột "image") được set làm ảnh
                    // đại diện (products.image) + ảnh chính trong gallery,
                    // các ảnh còn lại (image_2..image_6) thêm vào gallery.
                    $this->importImagesForProduct($product, $data, $rowNumber, $errors);

                    $created++;
                }
            }
        });

        return response()->json([
            'message' => "Hoàn tất: tạo mới {$created}, cộng dồn tồn kho {$updated} sản phẩm.",
            'created' => $created,
            'updated' => $updated,
            'errors'  => $errors,
        ]);
    }

    /**
     * PATCH /api/admin/products/{id}/toggle-bestseller
     */
    public function toggleBestseller($id)
    {
        $product = Product::findOrFail($id);
        $product->update(['is_bestseller' => !$product->is_bestseller]);

        return response()->json([
            'message'       => $product->is_bestseller ? 'Đã thêm vào Bán chạy' : 'Đã bỏ khỏi Bán chạy',
            'is_bestseller' => $product->is_bestseller,
        ]);
    }
    public function index(Request $request)
    {
        // Chỉ select các cột bảng danh sách admin thực sự hiển thị — tránh
        // kéo theo description/meta_description/meta_keywords/tags/og_image
        // (có thể khá dài) trên mỗi lần tải trang danh sách sản phẩm.
        $query = Product::select([
                'id', 'category_id', 'name', 'price', 'stock', 'image', 'is_bestseller', 'created_at',
            ])
            ->with('category:id,name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->latest()->paginate(15);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'category_id'    => 'required|exists:categories,id',
            'price'          => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'description'    => 'nullable|string',
            'stock'          => 'integer|min:0',
            'is_new'         => 'boolean',
            'is_active'      => 'boolean',
            'image'          => 'nullable|image|max:2048',
            'image_url'      => 'nullable|url|max:2048', // hoặc dán URL ảnh từ ngoài
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                                     ->store('products', 'public');
        } elseif (!empty($data['image_url'])) {
            $data['image'] = $data['image_url'];
        }
        unset($data['image_url']);

        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);

        $product = Product::create($data);

        // Tự động sinh thông số kỹ thuật mặc định theo danh mục + giá,
        // admin có thể chỉnh sửa lại sau qua API specifications bên dưới.
        $this->generateDefaultSpecifications($product);

        return response()->json($product->load('category'), 201);
    }

    public function show($id)
    {
        return response()->json(
            Product::with('category')->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'category_id'    => 'sometimes|exists:categories,id',
            'price'          => 'sometimes|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'description'    => 'nullable|string',
            'stock'          => 'integer|min:0',
            'is_new'         => 'boolean',
            'is_active'      => 'boolean',
            'image'          => 'nullable|image|max:2048',
            'image_url'      => 'nullable|url|max:2048', // hoặc dán URL ảnh từ ngoài
        ]);

        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu có (chỉ khi nó là file thật do hệ thống lưu,
            // không phải URL dán từ ngoài).
            if ($product->image && !is_external_image_url($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')
                                     ->store('products', 'public');
        } elseif (!empty($data['image_url'])) {
            if ($product->image && !is_external_image_url($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $data['image_url'];
        }
        unset($data['image_url']);

        $product->update($data);

        return response()->json($product->load('category'));
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Đã xóa sản phẩm']);
    }

    /**
     * Tải ảnh THẬT (theo URL trong file Excel/CSV) về server và gắn vào
     * gallery của sản phẩm (bảng product_images). Không dùng SVG placeholder.
     *
     * Cột "image" -> ảnh đại diện (products.image) + ảnh chính (is_primary)
     * Cột "image_2".."image_6" -> ảnh phụ trong gallery (tối đa 6 ảnh/dòng)
     *
     * Sản phẩm đã có đủ ảnh (>= 6) thì bỏ qua, không tải thêm — tránh
     * phình gallery khi import lại cùng 1 file nhiều lần.
     */
    private function importImagesForProduct(Product $product, array $data, int $rowNumber, array &$errors, bool $replaceExisting = false): void
    {
        $urls = [];
        foreach (['image', 'image_2', 'image_3', 'image_4', 'image_5', 'image_6'] as $col) {
            $url = trim((string) ($data[$col] ?? ''));
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        if (empty($urls)) {
            return;
        }

        // Chỉ xoá ảnh cũ khi file có ảnh mới để thay thế — tránh trường
        // hợp xoá sạch ảnh của sản phẩm chỉ vì dòng đó không điền URL ảnh.
        if ($replaceExisting) {
            foreach ($product->images as $oldImage) {
                if ($oldImage->image_url) {
                    Storage::disk('public')->delete($oldImage->image_url);
                }
                $oldImage->delete();
            }
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
                $product->update(['image' => null]);
            }
            $product->refresh();
        }

        $existingCount = $product->images()->count();
        if ($existingCount >= 6) {
            return;
        }

        $nextSortOrder = (int) ($product->images()->max('sort_order') ?? -1) + 1;

        foreach ($urls as $i => $url) {
            if ($product->images()->count() >= 6) {
                break;
            }

            // Không tải lại nếu URL này đã có sẵn trong gallery (import lại
            // cùng file nhiều lần sẽ không tạo ảnh trùng).
            if ($product->images()->where('image_url', $url)->exists()) {
                continue;
            }

            $storedPath = $this->downloadProductImage($url, $product->slug, $nextSortOrder + $i, $failReason);

            if (!$storedPath) {
                $reasonText = $failReason ? " (Lý do: {$failReason})" : '';
                $errors[] = "Dòng {$rowNumber}: Không tải được ảnh '{$url}' cho sản phẩm '{$product->name}', đã bỏ qua ảnh này.{$reasonText}";
                continue;
            }

            $isPrimary = $existingCount === 0 && $i === 0;

            ProductImage::create([
                'product_id' => $product->id,
                'image_url'  => $storedPath,
                'alt_text'   => $product->name,
                'sort_order' => $nextSortOrder + $i,
                'is_primary' => $isPrimary,
            ]);

            // Cột "image" (ảnh đầu tiên) luôn đồng bộ vào products.image
            // để các nơi hiển thị 1 ảnh (danh sách, giỏ hàng...) có ảnh thật.
            if ($isPrimary || empty($product->image)) {
                $product->update(['image' => $storedPath]);
            }
        }
    }

    /**
     * Tải 1 ảnh từ URL bên ngoài về storage/app/public/products.
     * Trả về đường dẫn tương đối (dùng cho asset('storage/...')) hoặc
     * null nếu tải thất bại (URL sai, hết thời gian chờ, không phải ảnh...).
     *
     * $failReason (tham chiếu, optional) được set khi trả về null, để nơi gọi
     * (importImagesForProduct) hiển thị lý do cụ thể cho admin thay vì chỉ
     * biết chung chung là "tải ảnh thất bại".
     *
     * LƯU Ý: nhiều CDN ảnh (Bing thumbnail, Apple, Amazon...) chặn request
     * không có User-Agent/Referer giống trình duyệt và trả về 403 — đây là
     * nguyên nhân phổ biến khiến ảnh "không được nhận" dù URL hợp lệ và mở
     * được bình thường trên trình duyệt.
     */
    private function downloadProductImage(string $url, string $slug, int $index, ?string &$failReason = null): ?string
    {
        $failReason = null;

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $failReason = 'URL không hợp lệ';
            return null;
        }

        try {
            $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                        . '(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept'     => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    // Referer = origin của chính URL ảnh, giúp qua được hotlink
                    // protection ở nhiều CDN (Bing, các trang thương mại điện tử...).
                    'Referer'    => parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . '/',
                ])
                ->timeout(20)
                ->connectTimeout(10)
                ->retry(2, 400)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);
        } catch (\Throwable $e) {
            $failReason = 'Lỗi kết nối: ' . $e->getMessage();
            return null;
        }

        if (!$response->successful()) {
            $failReason = 'HTTP ' . $response->status();
            return null;
        }

        $contentType = $response->header('Content-Type');
        if ($contentType && !str_starts_with((string) $contentType, 'image/')) {
            // Server trả về HTML (trang lỗi/redirect tới trang đăng nhập...)
            // thay vì file ảnh thật — không lưu để tránh lưu rác vào storage.
            $failReason = "Nội dung trả về không phải ảnh (Content-Type: {$contentType})";
            return null;
        }

        $extension = match (true) {
            str_contains((string) $contentType, 'png')  => 'png',
            str_contains((string) $contentType, 'webp') => 'webp',
            str_contains((string) $contentType, 'gif')  => 'gif',
            default => 'jpg',
        };

        $body = $response->body();
        if (empty($body)) {
            $failReason = 'Nội dung ảnh rỗng';
            return null;
        }

        $filename = "{$slug}-{$index}-" . Str::random(6) . ".{$extension}";
        $path = "products/{$filename}";

        Storage::disk('public')->put($path, $body);

        return $path;
    }

    /**
     * Sinh thông số kỹ thuật mặc định cho 1 sản phẩm dựa theo danh mục +
     * giá (dùng chung cho create() thủ công và import Excel hàng loạt).
     * Không ghi đè nếu sản phẩm đã có specs sẵn.
     */
    private function generateDefaultSpecifications(Product $product): void
    {
        if ($product->specifications()->exists()) {
            return;
        }

        $generator = new \App\Services\ProductSpecificationGenerator();
        $rows = $generator->generate(
            $product->category?->name ?? '',
            $product->name,
            (float) $product->price
        );

        if (empty($rows)) {
            return;
        }

        $now = now();
        $insert = [];
        foreach ($rows as $order => $row) {
            $insert[] = [
                'product_id' => $product->id,
                'group_name' => $row['group'],
                'label'      => $row['label'],
                'value'      => $row['value'],
                'unit'       => $row['unit'],
                'sort_order' => $order,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('product_specifications')->insert($insert);
    }

    /**
     * GET /api/admin/products/{id}/specifications
     */
    public function getSpecifications($id)
    {
        $product = Product::findOrFail($id);

        return response()->json(
            $product->specifications()->orderBy('sort_order')->get()
        );
    }

    /**
     * PUT /api/admin/products/{id}/specifications
     * Ghi đè toàn bộ danh sách thông số kỹ thuật của 1 sản phẩm (admin sửa
     * lại từ dữ liệu tự sinh, hoặc thêm/xoá dòng tuỳ ý).
     * Body: { specifications: [{ group_name, label, value, unit }, ...] }
     */
    public function updateSpecifications(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'specifications'             => 'array',
            'specifications.*.group_name' => 'required|string|max:255',
            'specifications.*.label'      => 'required|string|max:255',
            'specifications.*.value'      => 'required|string',
            'specifications.*.unit'       => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($product, $data) {
            $product->specifications()->delete();

            $now = now();
            $insert = [];
            foreach ($data['specifications'] ?? [] as $order => $row) {
                $insert[] = [
                    'product_id' => $product->id,
                    'group_name' => $row['group_name'],
                    'label'      => $row['label'],
                    'value'      => $row['value'],
                    'unit'       => $row['unit'] ?? null,
                    'sort_order' => $order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($insert)) {
                DB::table('product_specifications')->insert($insert);
            }
        });

        return response()->json(
            $product->specifications()->orderBy('sort_order')->get()
        );
    }

    /**
     * POST /api/admin/products/{id}/specifications/regenerate
     * Xoá specs hiện tại và sinh lại từ đầu theo danh mục + giá — hữu ích
     * khi admin đổi danh mục/giá và muốn làm mới bộ thông số mặc định.
     */
    public function regenerateSpecifications($id)
    {
        $product = Product::findOrFail($id);
        $product->specifications()->delete();
        $this->generateDefaultSpecifications($product);

        return response()->json(
            $product->specifications()->orderBy('sort_order')->get()
        );
    }
}