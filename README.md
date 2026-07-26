# Electro - Đồ Án Tốt Nghiệp

Dự án Hệ thống thương mại điện tử mua bán thiết bị điện tử "Electro" phục vụ cho Đồ Án Tốt Nghiệp. Dự án bao gồm các phân hệ chính:
- **Trang khách hàng (Storefront):** Xây dựng bằng Laravel Blade kết hợp Javascript, cung cấp giao diện mua sắm mượt mà, tính năng flash sale, đánh giá, tìm kiếm thông minh, và gợi ý cá nhân hóa thông qua chatbot.
- **Trang quản trị (Admin Panel):** Xây dựng bằng Vue.js 3 và Tailwind CSS (dưới dạng SPA), kết nối API với Backend, quản lý toàn bộ hệ thống từ sản phẩm, đơn hàng, người dùng đến các chiến dịch khuyến mãi.
- **Backend API:** Laravel Framework đóng vai trò xử lý logic nghiệp vụ, cơ sở dữ liệu và cung cấp API RESTful cho Admin Panel cũng như các tính năng của trang Storefront.

## 🚀 Các tính năng nổi bật
- **Khách hàng:** Đăng nhập, thêm vào giỏ hàng, đặt hàng (hỗ trợ thanh toán online / COD), chatbot thông minh hỗ trợ giải đáp tự động, đánh giá & bình luận sản phẩm, hiển thị gợi ý sản phẩm, Flash Sale.
- **Quản trị (Admin):** Thống kê và biểu đồ Insight doanh thu, quản lý hệ thống phân quyền, CRUD cho Sản phẩm/Danh mục/Người dùng/Voucher/Phương thức vận chuyển/Góp ý, xem và duyệt bình luận, quản lý các chiến dịch Flash Sale.
- **Đồng bộ cơ sở dữ liệu:** Tích hợp lệnh Artisan (`php artisan db:sync-prod`) để đồng bộ nhanh dữ liệu mẫu/sản phẩm từ Database Production (PostgreSQL) về môi trường Localhost (MySQL) chỉ trong một nốt nhạc phục vụ quá trình test.

## 🛠️ Công nghệ sử dụng
- **Backend:** PHP 8.3+, Laravel 11, Sanctum (Xác thực API), Eloquent ORM.
- **Cơ sở dữ liệu:** MySQL (Local), PostgreSQL (Render/Production).
- **Frontend (Khách):** HTML5, Bootstrap 5, Javascript thuần, jQuery.
- **Frontend (Admin):** Vue.js 3, Vite, Tailwind CSS, Axios, Vue Router, Pinia.
- **Lưu trữ ảnh:** Cloudinary API.

## ⚙️ Hướng dẫn cài đặt (Localhost)

### 1. Cài đặt Backend (Laravel)
- Clone kho lưu trữ về máy (vào thư mục `www` của WampServer hoặc XAMPP).
- Sao chép file cấu hình: `cp .env.example .env`
- Sửa lại thông tin kết nối Database trong `.env` cho phù hợp.
- Cài đặt thư viện: `composer install`
- Chạy migrate: `php artisan migrate` (Hoặc nếu muốn đồng bộ Data từ Production, chạy lệnh `php artisan db:sync-prod`)
- Tạo Key: `php artisan key:generate`
- Nếu không dùng WampServer mà dùng CLI mặc định: `php artisan serve`

### 2. Cài đặt Admin Panel (Vue.js)
- Mở Terminal và truy cập vào thư mục con: `cd admin-frontend`
- Cài đặt thư viện Node.js: `npm install`
- Chạy môi trường phát triển: `npm run dev`
- Truy cập vào trang admin trên Localhost thông qua cổng Vite cấp (ví dụ `http://localhost:5173`) hoặc thư mục WampServer (`http://localhost/doantotnghiep/admin-frontend`).

## 👨‍💻 Tác giả
- Phát triển bởi: BinhThanh313 và đội ngũ hỗ trợ.
- Phiên bản: 1.0.0