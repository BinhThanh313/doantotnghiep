import api from '@/services/api'

/**
 * Trả về URL hiển thị được cho MỌI kiểu giá trị ảnh trả về từ API
 * (product.image, image.image_url, variant.image...).
 *
 * Hỗ trợ đồng thời 2 dạng, giống hệt helper img_url() bên PHP
 * (app/helpers.php) để 2 phía luôn nhất quán:
 * 1) Đường dẫn tương đối do hệ thống tự lưu khi upload file lên storage
 *    (VD: "products/abc.jpg") -> ghép thành URL đầy đủ tới storage.
 * 2) URL tuyệt đối dán trực tiếp từ nơi khác (VD: link ảnh Google/Bing/CDN)
 *    -> giữ nguyên, không ghép gì thêm.
 *
 * @param {string|null|undefined} path
 * @param {string} [fallback] URL ảnh mặc định khi path rỗng
 */
export function imgUrl(path, fallback = '') {
  if (!path) return fallback

  // Đã là URL đầy đủ (http/https) hoặc URL không kèm scheme (//host/...)
  if (/^(https?:)?\/\//i.test(path)) {
    return path
  }

  // Đường dẫn tương đối -> ghép với baseURL hiện tại của API (không hard-code
  // localhost, để chạy đúng cả khi đổi domain/deploy lên server thật).
  const base = api.defaults.baseURL.replace(/\/$/, '')
  return `${base}/storage/${path.replace(/^\//, '')}`
}

/**
 * Kiểm tra 1 giá trị ảnh có phải URL dán từ ngoài hay không.
 */
export function isExternalImageUrl(path) {
  return !!path && /^(https?:)?\/\//i.test(path)
}