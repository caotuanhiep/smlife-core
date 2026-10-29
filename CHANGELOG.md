# Nhật ký thay đổi SMLIFE Core

## 1.26.0 - 29/10/2026

Hai module SEO, dựng sau khi soát Google Search Console. Cả hai mặc định TẮT, tự bật trong Bảng điều khiển.

### Mới
- **Schema sản phẩm**: bổ sung `shippingDetails`, `hasMerchantReturnPolicy` và `validFrom` vào schema sản phẩm. Thiếu ba trường này nên 4.172 sản phẩm của smlife.vn không vào được tab Shopping và khối sản phẩm phổ biến.
  - Phí giao tỉnh tính riêng cho từng sản phẩm theo công thức phí cố định cộng phí mỗi kg, lấy cân nặng thật của sản phẩm. Sản phẩm biến thể khai thành khoảng từ nhẹ nhất tới nặng nhất.
  - Nội thành TP.HCM khai riêng một vùng, mặc định miễn phí.
  - Bỏ khối `aggregateRating` ở sản phẩm chưa có đánh giá, sửa 184 lỗi rating ngoài khoảng và 360 lỗi reviewCount/ratingCount bằng 0.
  - Tự sinh SKU hợp lệ cho sản phẩm bỏ trống hoặc có ký tự lạ, gỡ `productGroupID` rỗng.
  - Chạy với Rank Math, có đường dự phòng cho Yoast WooCommerce SEO.
- **Tắt feed sản phẩm**: chuyển hướng 301 feed RSS của sản phẩm và danh mục về trang chủ, gỡ thẻ link feed khỏi `<head>`. Dọn nhóm lỗi 500 đang báo trong Search Console.


## 1.25.0 - 28/10/2026

Bản đầu tiên phát hành qua GitHub. Gồm toàn bộ 1.24.0 và 1.24.1.

### Mới
- Tự cập nhật từ GitHub: trang Plugin hiện "Có bản cập nhật" khi có bản mới trên nhánh main. Kho công khai nên không cần cấu hình thêm.
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
