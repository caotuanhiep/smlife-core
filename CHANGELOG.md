# Nhật ký thay đổi SMLIFE Core

## 1.29.0 - 30/10/2026

### Mới
- Đánh giá sản phẩm: sản phẩm chưa có đánh giá không còn bỏ trống. Dưới tên sản phẩm hiện 5 sao xám kèm chữ "Chưa có đánh giá", chỗ khối đánh giá hiện khung mời khách gửi đánh giá, có nút gửi qua Zalo (điền link trong cài đặt, để trống thì ẩn nút).
- Không có điểm số nên không tạo dữ liệu đánh giá cho Google. Bật tắt và sửa chữ ở SMLIFE Core > Đánh giá sản phẩm > Hiển thị, mặc định BẬT.

## 1.28.0 - 30/10/2026

### Thay đổi
- Lịch Trực Sales: bảng Lịch cuối tuần hiện sẵn 14 tuần (khoảng 3 tháng) thay vì 4 tuần, chia theo tháng, để nhân sự xếp một lần cho cả quý. Tuần đã qua tự biến mất, tuần mới tự thêm vào cuối bảng.
- Thêm nút "Xếp xoay vòng các tuần còn trống" (điền lần lượt theo thứ tự nhân sự, nối tiếp người đã xếp cuối cùng), nút "Bỏ trống tất cả" và dòng đếm số Chủ nhật của mỗi người. Hai nút chỉ điền vào bảng, phải bấm Lưu mới ghi.

## 1.27.2 - 30/10/2026

### Sửa lỗi
- Mô tả meta sản phẩm: Rank Math lấy mô tả ngắn của sản phẩm thay cho mẫu "Single Product Description", nên trên Google vẫn hiện chữ dính ("Cao 140cmChất liệu"). Khi module Schema sản phẩm bật, sản phẩm nào chưa tự viết mô tả SEO riêng sẽ dùng đúng mẫu đã đặt trong Rank Math (cả og:description).

## 1.27.1 - 30/10/2026

### Sửa lỗi
- Schema sản phẩm: sản phẩm biến thể (Rank Math xuất dạng ProductGroup, giá nằm trong từng biến thể) trước đây không được thêm shippingDetails, hasMerchantReturnPolicy, validFrom. Giờ bổ sung vào từng biến thể. Phần lớn sản phẩm smlife.vn là biến thể nên đây là nguyên nhân Merchant listings vẫn báo thiếu thông tin giao hàng và đổi trả.
- Schema sản phẩm: productGroupID của sản phẩm biến thể luôn là SKU hợp lệ; SKU biến thể trùng SKU nhóm hoặc sai định dạng thì đặt lại thành SKU nhóm + số thứ tự; tên sản phẩm quá 150 ký tự thì cắt gọn. Dọn các lỗi "Invalid value in field sku/productGroupID" và "Invalid string length in field name".

## 1.27.0 - 30/10/2026

### Mới
- **Canh đường dẫn** (mặc định BẬT): mỗi 10 phút, trong lượt cron, gọi thử sitemap_index.xml của Rank Math và một trang danh mục sản phẩm. Hỏng (404, bị chuyển về trang chủ, mở nhầm chuyên mục blog) thì lượt truy cập kế tiếp tự tạo lại bảng đường dẫn như bấm Lưu ở Đường dẫn tĩnh, xoá cache WP Rocket/LiteSpeed và gửi email. Tự sửa 2 lần vẫn hỏng thì dừng và gửi email báo xử lý tay.
- Ghi lại mỗi lần bảng quy tắc đường dẫn bị tạo lại (giờ, kiểu truy cập, URL, plugin gọi) để tìm plugin gây lỗi. Xem ở SMLIFE Core > Canh đường dẫn.

## 1.26.5 - 30/10/2026

### Sửa lỗi
- Lịch xoá cache: hết giằng co .htaccess với Solid Security. Lượt quét mỗi giờ của Solid ghi lại file và lệch một dòng trống, SMLIFE Core 15 phút sau lại ghi đè, nhật ký đầy dòng .htaccess che mất dòng lịch đêm. Giờ khối ngoại lệ đã đúng nội dung và nằm trước khối Solid thì không ghi lại.
- Nhật ký chỉ ghi "File .htaccess bị sửa" khi nội dung thật sự đổi (bỏ qua dòng trống), không ghi khi chỉ đổi giờ sửa file.

## 1.26.4 - 29/10/2026

### Sửa lỗi
- Cập nhật tự động từ GitHub báo lỗi HTTP 415: sửa header tải gói zipball (application/vnd.github+json).

## 1.26.3 - 29/10/2026

### Mới
- **Đánh giá cửa hàng Google**: hộp đồng ý khảo sát Google Customer Reviews trên trang Đặt hàng thành công. Bỏ qua đơn không có email, mỗi đơn chỉ hỏi một lần. Mặc định TẮT, cần nhập Merchant ID.

### Thay đổi
- Schema sản phẩm: thêm ô "Freeship toàn quốc", mặc định BẬT. Khai một vùng Việt Nam miễn phí, giao 1-3 ngày, khớp Merchant Center.

## 1.26.2 - 29/10/2026

- Schema sản phẩm: mặc định khớp với Merchant Center của smlife.vn. Đổi trả mặc định "Mang tới showroom" (Merchant Center đang khai trả tại cửa hàng, miễn phí, 7 ngày). Giao tỉnh mặc định 5-7 ngày tổng. Thêm ghi chú phải khớp với Merchant Center trên trang cài đặt.

## 1.26.1 - 29/10/2026

- Schema sản phẩm: thêm ô "Phí khách trả khi gửi về". Google bắt buộc khai số tiền khi khách tự trả phí đổi trả, bỏ trống thì cả khối chính sách bị coi là không hợp lệ. Mặc định đổi sang "Shop trả".

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
