<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Quản lý gallery ảnh (bảng product_images) cho TỪNG sản phẩm, theo kiểu
 * thêm/sửa/xoá TỪNG ảnH một trong màn hình Sửa sản phẩm.
 *
 * Trước đây gallery chỉ có thể được nạp hàng loạt qua ProductController::import()
 * (nhập ảnh từ file Excel/CSV). Controller này bổ sung thao tác thủ công cho
 * từng ảnh, dùng ở admin ProductFormView khi đang ở chế độ Sửa.
 *
 * Quy ước:
 * - Tối đa 6 ảnh / sản phẩm (đồng bộ với giới hạn của import()).
 * - Luôn có đúng 1 ảnh is_primary = true khi gallery không rỗng.
 * - Ảnh primary luôn được đồng bộ ngược vào products.image để các nơi chỉ
 *   hiển thị 1 ảnh (danh sách, giỏ hàng...) vẫn có ảnh đúng.
 */
class ProductImageController extends Controller
{
    private const MAX_IMAGES = 6;

    /**
     * GET /api/admin/products/{productId}/images
     */
    public function index($productId)
    {
        $product = Product::findOrFail($productId);

        return response()->json(
            $product->images()->orderBy('sort_order')->get()
        );
    }

    /**
     * POST /api/admin/products/{productId}/images
     * Thêm MỘT ảnh vào gallery (multipart/form-data).
     * Body: image (file, required), alt_text (nullable), is_primary (nullable boolean)
     */
    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $data = $request->validate([
            'image'      => 'nullable|image|max:2048',
            'image_url'  => 'nullable|url|max:2048', // dán URL ảnh từ ngoài (VD: link ảnh Bing/Google/CDN)
            'alt_text'   => 'nullable|string|max:255',
            'is_primary' => 'nullable|boolean',
        ]);

        if (!$request->hasFile('image') && empty($data['image_url'])) {
            return response()->json([
                'message' => 'Cần chọn 1 file ảnh để upload hoặc dán URL ảnh.',
            ], 422);
        }

        if ($product->images()->count() >= self::MAX_IMAGES) {
            return response()->json([
                'message' => 'Sản phẩm đã đủ ' . self::MAX_IMAGES . ' ảnh, hãy xoá bớt trước khi thêm ảnh mới.',
            ], 422);
        }

        // Ưu tiên file upload nếu có, ngược lại dùng URL dán trực tiếp
        // (lưu nguyên URL vào image_url, img_url() helper sẽ tự nhận diện
        // và không ghép thêm "storage/" khi hiển thị).
        $path = $request->hasFile('image')
            ? CloudinaryService::upload($request->file('image'), 'products')
            : $data['image_url'];

        $isFirstImage = $product->images()->count() === 0;
        $wantsPrimary = $request->boolean('is_primary') || $isFirstImage;

        $nextSortOrder = (int) ($product->images()->max('sort_order') ?? -1) + 1;

        $image = ProductImage::create([
            'product_id' => $product->id,
            'image_url'  => $path,
            'alt_text'   => $data['alt_text'] ?? $product->name,
            'sort_order' => $nextSortOrder,
            'is_primary' => $wantsPrimary,
        ]);

        if ($wantsPrimary) {
            $this->makePrimary($product, $image);
        }

        return response()->json($image->fresh(), 201);
    }

    /**
     * PUT/POST /api/admin/products/{productId}/images/{imageId}
     * Sửa MỘT ảnh: thay file ảnh, đổi alt_text, đổi thứ tự, hoặc đặt làm ảnh chính.
     * Dùng multipart/form-data + _method=PUT nếu có thay file (giống pattern
     * cập nhật sản phẩm hiện tại), hoặc PUT JSON thường nếu chỉ đổi metadata.
     */
    public function update(Request $request, $productId, $imageId)
    {
        $product = Product::findOrFail($productId);
        $image = $product->images()->findOrFail($imageId);

        $data = $request->validate([
            'image'      => 'nullable|image|max:2048',
            'image_url'  => 'nullable|url|max:2048', // dán URL ảnh từ ngoài để thay ảnh hiện tại
            'alt_text'   => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            // Xóa file cũ (Cloudinary hoặc local)
            CloudinaryService::delete($image->image_url);
            $image->image_url = CloudinaryService::upload($request->file('image'), 'products');
        } elseif (!empty($data['image_url'])) {
            CloudinaryService::delete($image->image_url);
            $image->image_url = $data['image_url'];
        }

        if (array_key_exists('alt_text', $data) && $data['alt_text'] !== null) {
            $image->alt_text = $data['alt_text'];
        }

        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $image->sort_order = $data['sort_order'];
        }

        $image->save();

        if ($request->boolean('is_primary')) {
            $this->makePrimary($product, $image);
        } elseif ($image->is_primary) {
            // Ảnh này đang là primary và request có thay ảnh -> đồng bộ lại
            // products.image cho khớp file/URL mới.
            $product->update(['image' => $image->image_url]);
        }

        return response()->json($image->fresh());
    }

    /**
     * DELETE /api/admin/products/{productId}/images/{imageId}
     * Xoá MỘT ảnh khỏi gallery. Nếu ảnh bị xoá đang là ảnh chính, tự động
     * gán ảnh chính mới (ảnh có sort_order nhỏ nhất còn lại), hoặc xoá
     * products.image nếu không còn ảnh nào.
     */
    public function destroy($productId, $imageId)
    {
        $product = Product::findOrFail($productId);
        $image = $product->images()->findOrFail($imageId);

        $wasPrimary = $image->is_primary;

        if ($image->image_url) {
            CloudinaryService::delete($image->image_url);
        }
        $image->delete();

        if ($wasPrimary) {
            $nextPrimary = $product->images()->orderBy('sort_order')->first();
            if ($nextPrimary) {
                $this->makePrimary($product, $nextPrimary);
            } else {
                $product->update(['image' => null]);
            }
        }

        return response()->json(['message' => 'Đã xoá ảnh']);
    }

    /**
     * PATCH /api/admin/products/{productId}/images/reorder
     * Sắp xếp lại thứ tự gallery.
     * Body: { order: [imageId1, imageId2, ...] } theo đúng thứ tự mong muốn.
     */
    public function reorder(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $data = $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:product_images,id',
        ]);

        foreach ($data['order'] as $index => $imageId) {
            $product->images()->where('id', $imageId)->update(['sort_order' => $index]);
        }

        return response()->json(
            $product->images()->orderBy('sort_order')->get()
        );
    }

    /**
     * Đặt 1 ảnh làm ảnh chính (is_primary) cho sản phẩm, bỏ cờ primary ở các
     * ảnh còn lại, và đồng bộ vào products.image.
     */
    private function makePrimary(Product $product, ProductImage $image): void
    {
        $product->images()->where('id', '!=', $image->id)->update(['is_primary' => false]);

        if (!$image->is_primary) {
            $image->update(['is_primary' => true]);
        }

        $product->update(['image' => $image->image_url]);
    }
}