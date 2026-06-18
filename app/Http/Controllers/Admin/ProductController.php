<?php
namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
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
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

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

        DB::transaction(function () use ($rows, $header, &$created, &$updated, &$errors, $userId) {
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
        $query = Product::with('category');

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
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                                     ->store('products', 'public');
        }

        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);

        $product = Product::create($data);

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
        ]);

        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu có
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')
                                     ->store('products', 'public');
        }

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
}