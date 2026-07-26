# Electro - Đồ Án Tốt Nghiệp

Dự án Hệ thống thương mại điện tử mua bán thiết bị điện tử "Electro" phục vụ cho Đồ Án Tốt Nghiệp. Dự án được phân chia rõ ràng thành hai phân hệ độc lập về mặt giao diện nhưng sử dụng chung một hệ thống API mạnh mẽ.

## 🚀 Các tính năng nổi bật
- **Khách hàng (Storefront):** 
  - Giao diện mua sắm mượt mà, tương thích nhiều kích thước màn hình.
  - Đăng nhập, quản lý giỏ hàng, đặt hàng (hỗ trợ thanh toán online / thanh toán khi nhận hàng).
  - Tích hợp Chatbot AI hỗ trợ giải đáp thắc mắc tự động và tư vấn sản phẩm.
  - Hệ thống Đánh giá & bình luận sản phẩm chân thực.
  - Hiển thị gợi ý sản phẩm cá nhân hóa.
  - Tham gia các chiến dịch Flash Sale với đồng hồ đếm ngược.
- **Quản trị (Admin Panel):** 
  - Hoạt động dưới dạng Ứng dụng trang đơn (SPA) giúp thao tác chuyển trang tức thì, không cần tải lại.
  - Thống kê, báo cáo và biểu đồ Insight doanh thu, gợi ý chiến lược kinh doanh.
  - Quản lý hệ thống phân quyền bảo mật cao (Bearer Token).
  - CRUD (Thêm, Đọc, Sửa, Xóa) đầy đủ cho: Sản phẩm, Danh mục, Người dùng, Voucher, Phương thức vận chuyển, Liên hệ.
  - Theo dõi và cập nhật trạng thái Đơn hàng, kiểm duyệt bình luận.

## 🛠️ Công nghệ sử dụng
- **Backend (Server & API):** PHP 8.3+, Laravel 11, Sanctum (Xác thực Token), Eloquent ORM.
- **Cơ sở dữ liệu:** Hệ quản trị cơ sở dữ liệu quan hệ (RDBMS).
- **Frontend (Giao diện Khách hàng):** HTML5, Bootstrap 5, Javascript thuần, jQuery, Blade Template.
- **Frontend (Giao diện Admin):** Vue.js 3 (Composition API), Vite, Tailwind CSS, Axios, Vue Router, Pinia.
- **Dịch vụ bên thứ ba:** Cloudinary API (Lưu trữ và tối ưu hóa hình ảnh).

## 📋 Yêu cầu hệ thống (Prerequisites)
Để chạy dự án, máy tính của bạn cần cài đặt sẵn:
- PHP >= 8.3
- Composer
- Node.js >= 18 và npm
- Cơ sở dữ liệu MySQL (có thể dùng WampServer, XAMPP, Laragon...)

## ⚙️ Hướng dẫn cài đặt (Localhost)

### 1. Cài đặt Backend (Laravel)
- Mở Terminal, clone kho lưu trữ về máy (vào thư mục `www` của WampServer hoặc `htdocs` của XAMPP).
- Sao chép file cấu hình môi trường: 
  ```bash
  cp .env.example .env
  ```
- Mở file `.env` và sửa lại thông tin kết nối Cơ sở dữ liệu của máy bạn (chủ yếu là `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
- Cài đặt các thư viện PHP: 
  ```bash
  composer install
  ```
- Chạy lệnh khởi tạo các bảng cơ sở dữ liệu và nạp dữ liệu mẫu ban đầu:
  ```bash
  php artisan migrate --seed
  ```
- Tạo Application Key cho Laravel: 
  ```bash
  php artisan key:generate
  ```
- *(Tùy chọn)* Nếu bạn không chạy qua phần mềm ảo hóa WampServer, bạn có thể khởi động server ảo của Laravel bằng lệnh: 
  ```bash
  php artisan serve
  ```

### 2. Cài đặt Admin Panel (Vue.js)
- Mở Terminal mới và di chuyển vào thư mục chứa mã nguồn Admin: 
  ```bash
  cd admin-frontend
  ```
- Cài đặt các thư viện Node.js: 
  ```bash
  npm install
  ```
- Chạy môi trường phát triển (Hot-reload): 
  ```bash
  npm run dev
  ```
- Bây giờ bạn có thể truy cập vào trang Admin trên Localhost thông qua cổng Vite cấp (ví dụ `http://localhost:5173`) để vừa code vừa xem kết quả ngay lập tức. Để triển khai thật, bạn có thể chạy `npm run build` để xuất file.

## 🔒 Tài khoản quản trị mẫu
Sau khi chạy lệnh migrate và seed cơ sở dữ liệu, hệ thống thường sẽ tạo sẵn một tài khoản Admin để truy cập thử nghiệm.
- **Email:** `admin@gmail.com` *(hoặc xem trong file Database Seeder)*
- **Mật khẩu:** `password` *(hoặc mật khẩu mặc định bạn đã cấu hình)*

## 📸 Ảnh minh họa (Screenshots)
*(Gợi ý: Dán một vài hình ảnh chụp màn hình trang chủ và giao diện biểu đồ của Admin vào đây để file README trở nên sinh động và gây ấn tượng mạnh với người chấm đồ án nhé!)*