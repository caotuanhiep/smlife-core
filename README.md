# SMLIFE Core

Plugin WordPress vận hành website cho hệ sinh thái SMLIFE (smlife.vn và các website vệ tinh). Bật tắt từng module trong **SMLIFE Core > Bảng điều khiển**.

## Tự cập nhật từ GitHub

Từ bản 1.25.0, mỗi website cài SMLIFE Core tự đọc dòng `Version:` của `smlife-core.php` trên nhánh **main** (6 tiếng một lần). Bản trên main mới hơn bản đang chạy thì trang Plugin hiện "Có bản cập nhật", bấm Cập nhật là xong. Không cần Release hay tag.

Kho đang để công khai nên không cần cấu hình gì. Nếu sau này chuyển về riêng tư, thêm vào `wp-config.php` của từng website (trước dòng `require_once ABSPATH . 'wp-settings.php';`):

```php
define( 'SMLIFE_CORE_GH_TOKEN', 'github_pat_...' ); // Fine-grained token, chỉ quyền Contents: Read-only cho kho smlife-core
```

Tuỳ chọn:

```php
define( 'SMLIFE_CORE_BRAND', 'SMLIFE' );        // Tên thương hiệu dùng cho mặc định email, menu
define( 'SMLIFE_CORE_GH_BRANCH', 'main' );      // Nhánh phát hành
```

## Phát hành bản mới

1. Tăng version ở 2 chỗ trong `smlife-core.php` (dòng `Version:` và `SMLIFE_CORE_VER`), ghi vào `CHANGELOG.md`.
2. `php -l` toàn bộ file, chạy test.
3. Commit và đẩy lên nhánh `main`. Đẩy lên main là phát hành, nên chỉ đẩy bản đã test.
4. Cập nhật smlife.vn trước (Bảng điều khiển > Kiểm tra cập nhật ngay), chạy ổn 1-2 ngày rồi mới cập nhật các website còn lại.

Cài đặt của từng website nằm trong database của website đó, cập nhật code không ảnh hưởng.
