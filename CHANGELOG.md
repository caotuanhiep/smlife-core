# Nhật ký thay đổi SMLIFE Core

## 1.25.0 - 28/10/2026

Bản đầu tiên phát hành qua GitHub. Gồm toàn bộ 1.24.0 và 1.24.1.

### Mới
- Tự cập nhật từ GitHub: trang Plugin hiện "Có bản cập nhật" khi có bản mới trên nhánh main. Cần `SMLIFE_CORE_GH_TOKEN` trong wp-config.php.
- Bảng điều khiển có dòng trạng thái cập nhật và nút "Kiểm tra cập nhật ngay".
- Tên thương hiệu trong mặc định Email Center và Menu mobile lấy theo từng website.

### 1.24.1
- Email đơn hàng: địa chỉ gom một dòng, cột tên sản phẩm giãn rộng, dòng tổng tách bảng riêng.

### 1.24.0
- Lưu cài đặt chờ lên web lúc 00:15, có nút "Đưa lên web ngay".
- Dynamic Shortcode: lưu không xoá cấu hình các danh mục khác, chặn lưu khi dữ liệu bị cắt.
- Khoá chống chạy trùng lưu trong database; job đêm trễ quá 60 phút không xoá cache giữa ngày.
- .htaccess: bản sao an toàn, khôi phục nguyên file khi bị xoá trắng.
- Chống spam form liên hệ; email đơn hàng hiện giá trước giảm; sửa nhỏ và hiệu năng.
