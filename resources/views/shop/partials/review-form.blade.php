<div class="review-form-wrapper mt-5 pt-4 border-top">
    <h4 class="mb-4">Viết đánh giá của bạn</h4>
    <form id="review-form" onsubmit="submitReview(event)">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Điểm đánh giá <span class="text-danger">*</span></label>
                <select name="rating" id="review-rating" class="form-select" required>
                    <option value="5" selected>⭐⭐⭐⭐⭐ (5) Tuyệt vời</option>
                    <option value="4">⭐⭐⭐⭐ (4) Rất tốt</option>
                    <option value="3">⭐⭐⭐ (3) Bình thường</option>
                    <option value="2">⭐⭐ (2) Kém</option>
                    <option value="1">⭐ (1) Rất tệ</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tiêu đề (Không bắt buộc)</label>
                <input type="text" name="title" id="review-title" class="form-control" placeholder="Tóm tắt đánh giá">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Nội dung đánh giá</label>
            <textarea name="comment" id="review-comment" class="form-control" rows="4" placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Hình ảnh đính kèm (Tối đa 2MB/ảnh)</label>
            <input type="file" name="images[]" id="review-images" class="form-control" multiple accept="image/*">
        </div>
        
        <button type="submit" class="btn btn-primary text-white rounded-pill px-4 py-2" id="submit-review-btn">
            Gửi đánh giá
        </button>
    </form>
    <div id="review-message" class="mt-3"></div>
</div>