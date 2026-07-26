# 📱 Hệ thống Thương mại Điện tử Electro

## 📖 Giới thiệu

**Electro** là một hệ thống thương mại điện tử chuyên cung cấp và mua bán các thiết bị điện tử hiện đại. Dự án được xây dựng phục vụ cho Đồ Án Tốt Nghiệp, áp dụng kiến trúc phân tách rõ ràng giữa trang dành cho Khách hàng (Storefront) và trang Quản trị viên (Admin Panel), kết hợp cùng các công nghệ web tiên tiến nhất hiện nay để mang lại trải nghiệm mượt mà, bảo mật và dễ dàng mở rộng.

### 🎯 Mục tiêu dự án
- Xây dựng nền tảng mua sắm trực tuyến nhanh chóng, trực quan cho người tiêu dùng.
- Cung cấp hệ thống quản lý (Dashboard) toàn diện, mạnh mẽ cho chủ cửa hàng.
- Tích hợp trí tuệ nhân tạo (Chatbot) để tự động hóa khâu tư vấn khách hàng.
- Áp dụng kiến trúc API-first và Single Page Application (SPA) cho luồng quản trị.

---

## 🛠️ Công nghệ và Công cụ sử dụng

### 🖥️ Backend Framework & API
- **Laravel 11.x** - PHP Framework xử lý logic và cung cấp RESTful API.
- **PHP 8.3+** - Ngôn ngữ lập trình chính.
- **MySQL / PostgreSQL** - Hệ quản trị Cơ sở dữ liệu.
- **Laravel Sanctum** - Quản lý xác thực an toàn bằng Token (Bearer).
- **Eloquent ORM** - Giao tiếp và xử lý dữ liệu.

### 🎨 Frontend (Storefront - Khách hàng)
- **Blade Template** - Server-side rendering cho SEO tốt hơn.
- **Bootstrap 5** - Framework CSS xây dựng giao diện responsive.
- **JavaScript & jQuery** - Xử lý các tương tác động trên trang.

### ⚡ Frontend (Admin Panel - Quản trị)
- **Vue.js 3 (Composition API)** - Xây dựng Single Page Application (SPA).
- **Tailwind CSS 3.x** - Framework CSS tiện ích cho giao diện hiện đại.
- **Vite** - Công cụ build siêu tốc và dev server.
- **Pinia** - Quản lý trạng thái (State management).
- **Axios** - Tương tác HTTP với Backend API.

### ☁️ Dịch vụ bên thứ ba & Tích hợp
- **Cloudinary API** - Nền tảng lưu trữ, quản lý và tối ưu hóa hình ảnh đám mây.
- **Groq AI / Chatbot** - Tích hợp AI xử lý ngôn ngữ tự nhiên siêu tốc để hỗ trợ tư vấn khách hàng.

---

## 📋 Yêu cầu hệ thống

### Yêu cầu tối thiểu
- **PHP:** 8.3 hoặc cao hơn.
- **Composer:** 2.0+
- **Node.js:** 18.0+ & **NPM** (để quản lý và build Vue.js).
- **Database:** MySQL 8.0+ hoặc MariaDB.
- **Web Server:** Apache / Nginx (Khuyên dùng WampServer, XAMPP hoặc Laragon cho môi trường Local).

---

## 🚀 Hướng dẫn cài đặt (Localhost)

### Bước 1: Clone mã nguồn và Cài đặt Backend
Di chuyển vào thư mục web server của bạn (VD: `C:\wamp64\www\`) và mở Terminal:

```bash
# Clone repository về máy
git clone <link-repo-cua-ban> doantotnghiep
cd doantotnghiep

# Cài đặt PHP dependencies
composer install

# Sao chép file môi trường
cp .env.example .env

# Tạo Application Key
php artisan key:generate
```

### Bước 2: Cấu hình Cơ sở dữ liệu & Cloudinary
Mở file `.env` và cập nhật các thông tin sau:

```env
# Cấu hình Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cấu hình Cloudinary (Bắt buộc để ảnh hoạt động)
CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME
```

### Bước 3: Khởi tạo Dữ liệu
```bash
# Chạy migration tạo bảng và seed dữ liệu mẫu ban đầu
php artisan migrate --seed

# Tạo symbolic link cho thư mục lưu trữ nội bộ
php artisan storage:link
```

### Bước 4: Cài đặt và Build Admin Panel (Vue.js)
Mở một Terminal mới và di chuyển vào thư mục frontend:

```bash
cd admin-frontend

# Cài đặt Node modules
npm install

# (Lựa chọn 1) Chạy server phát triển (Hot-reload) để code
npm run dev

# (Lựa chọn 2) Build ra file tĩnh cho Production
npm run build
```

### Bước 5: Khởi chạy ứng dụng
Nếu bạn không dùng WampServer/XAMPP, hãy chạy server ảo của Laravel:
```bash
php artisan serve
```
- **Trang khách hàng:** `http://localhost:8000` (hoặc `http://localhost/doantotnghiep/public`)
- **Trang quản trị:** `http://localhost:5173` (Vue Dev) hoặc `http://localhost:8000/admin` (Sau khi đã build)

---

## 👥 Hệ thống phân quyền & Tính năng chính

### 1. Phân hệ Khách hàng (Storefront)
- **Đăng ký / Đăng nhập:** Hệ thống tài khoản người dùng an toàn.
- **Tìm kiếm & Lọc:** Tìm kiếm sản phẩm theo tên, danh mục, giá tiền, thương hiệu.
- **Mua sắm & Giỏ hàng:** Quản lý giỏ hàng thông minh, hỗ trợ thanh toán khi nhận hàng (COD).
- **Đánh giá & Bình luận:** Cho phép khách hàng để lại sao và nhận xét (có đính kèm hình ảnh/video).
- **Trợ lý ảo (Chatbot):** Nằm ở góc màn hình, tự động trả lời các câu hỏi về thông số sản phẩm, hướng dẫn mua hàng.
- **Flash Sale:** Chương trình giảm giá chớp nhoáng với đồng hồ đếm ngược thời gian thực.

### 2. Phân hệ Quản trị viên (Admin Panel)
- **Tổng quan (Dashboard):** Thống kê doanh thu, số lượng đơn hàng, Insight và phân tích xu hướng mua hàng.
- **Quản lý Sản phẩm:** Thêm mới, sửa đổi, cập nhật kho, hình ảnh Gallery, và thông số kỹ thuật chi tiết.
- **Quản lý Đơn hàng:** Theo dõi trạng thái đơn hàng (Chờ duyệt, Đang giao, Hoàn thành, Đã hủy).
- **Quản lý Khuyến mãi:** Tạo mã giảm giá (Voucher), thiết lập chiến dịch Flash Sale.
- **Quản lý Khách hàng & Đánh giá:** Khóa/Mở khóa tài khoản, duyệt hoặc ẩn các bình luận tiêu cực/vi phạm.

---

## 📊 Cấu trúc thư mục cốt lõi

```text
doantotnghiep/
├── admin-frontend/         # 🟢 [VUE.JS] Mã nguồn giao diện Quản trị (SPA)
│   ├── src/
│   │   ├── components/     # Các UI Components tái sử dụng
│   │   ├── views/          # Các trang chức năng (Dashboard, Products,...)
│   │   └── services/       # Cấu hình Axios gọi API
│   └── vite.config.js      # Cấu hình build Vite
├── app/                    # 🔵 [LARAVEL] Logic Backend chính
│   ├── Http/Controllers/   # API Controllers & Web Controllers
│   ├── Models/             # Eloquent Models
│   └── Console/Commands/   # Các custom command CLI
├── database/               # File cấu trúc bảng (Migrations) & Dữ liệu mẫu (Seeders)
├── resources/              # 🟡 [BLADE] Giao diện trang Khách hàng
│   ├── views/              # Layout & Pages HTML
│   └── css/ & js/          # Assets tĩnh cho Storefront
├── routes/                 # Định tuyến URL (web.php, api.php)
└── public/                 # Chứa thư mục build của Admin (/admin) & assets public
```

---

## 🚀 Deployment (Môi trường Production)

Để đưa dự án lên môi trường thực tế (như VPS, Shared Hosting hoặc nền tảng Cloud như Render/Heroku), bạn cần thực hiện các bước tối ưu hóa sau:

### 1. Cấu hình Môi trường
Cập nhật file `.env` cho Production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

### 2. Tối ưu hóa Backend (Laravel)
Chạy các lệnh cache để tăng tốc độ xử lý:
```bash
# Cài đặt thư viện không chứa các gói dev
composer install --optimize-autoloader --no-dev

# Xóa và tạo lại toàn bộ Cache hệ thống
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Tối ưu hóa Frontend (Vue.js Admin)
Trên server hoặc máy local, build mã nguồn Vue ra HTML/JS/CSS tĩnh:
```bash
cd admin-frontend
npm install
npm run build
```
*(Thư mục `public/admin` sẽ được cập nhật. Bạn chỉ cần đưa thư mục `public` lên Web Server).*

### 4. Phân quyền thư mục (Dành cho Linux/VPS)
Đảm bảo máy chủ Web (Nginx/Apache) có quyền ghi vào thư mục `storage` và `bootstrap/cache`:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 🔒 Tài khoản dùng thử nghiệm (Testing Credentials)
Hệ thống tạo sẵn tài khoản quản trị để kiểm thử (sau khi chạy `--seed`):
- **Email:** `admin@gmail.com`
- **Mật khẩu:** `password`

---

## 📸 Ảnh minh họa (Screenshots)

*Giao diện trang chủ Khách hàng*
![Storefront Homepage](screenshots/storefront.png)

*Giao diện Bảng điều khiển Quản trị (Vue.js Dashboard)*
![Admin Dashboard](screenshots/admin.png)

---
**Tác giả:** BinhThanh313
**Năm hoàn thành:** 2026
