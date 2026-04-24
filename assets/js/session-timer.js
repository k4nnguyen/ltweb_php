/**
 * Session Timer - Update countdown mà không cần refresh page
 * Gọi AJAX session-info.php mỗi giây để lấy thời gian còn lại
 */

$(document).ready(function () {
  // Update session timer mỗi 1 giây
  setInterval(updateSessionTimer, 1000);

  // Gọi ngay lần đầu
  updateSessionTimer();
});

function updateSessionTimer() {
  // Chỉ chạy nếu navbar đã render xong
  const timerElement = $('[title="Thời gian còn lại trước khi tự động logout"]');
  if (timerElement.length === 0) return;

  $.ajax({
    url: "/admin_vi_pham/ajax/session-info.php",
    method: "POST",
    dataType: "json",
    timeout: 3000,
    success: function (data) {
      if (data.success) {
        // Cập nhật text thời gian
        let timeText = data.remaining_formatted;
        let htmlContent = '<i class="fa-solid fa-hourglass-end me-1"></i><small>';

        // Nếu < 5 phút, đổi sang màu đỏ
        if (data.is_critical) {
          htmlContent += '<span class="text-danger">' + timeText + "</span>";
        } else {
          htmlContent += timeText;
        }
        htmlContent += "</small>";

        timerElement.html(htmlContent);

        // Nếu hết thời gian, redirect
        if (data.remaining_seconds <= 0) {
          window.location.href = "/admin_vi_pham/admin/login.php?timeout=1";
        }
      } else {
        // Session hết hạn, redirect
        window.location.href = "/admin_vi_pham/admin/login.php?timeout=1";
      }
    },
    error: function () {
      // Nếu lỗi AJAX, có thể session hết hạn
      console.warn("Không thể kết nối session server");
    },
  });
}
