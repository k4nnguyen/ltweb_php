// assets/js/form_handler.js
// Script này được load ở footer SAU jQuery

console.log("✓ form_handler.js loaded");
console.log("jQuery version:", $.fn.jquery);

// Helper function để set dropdown value và force update UI
function setSelectValue(selector, value) {
  console.log(`[setSelectValue] selector: ${selector}, value: '${value}'`);
  $(selector).val(value);
  // Bắt buộc render lại
  $(selector).find("option").prop("selected", false);
  $(selector)
    .find("option[value='" + value + "']")
    .prop("selected", true);
  console.log(`[setSelectValue] After setting, select value is now: '${$(selector).val()}'`);
  $(selector).trigger("change");
}

function resetForm() {
  console.log("📝 resetForm() called");
  $("#modalTitle").html('<i class="fa-solid fa-file-circle-plus me-2"></i>Lập Hồ Sơ Vi Phạm');
  $("#frmAction").val("add");
  $("#frmMaHoSo").val("");
  $("#frmBienSoXe").val("").prop("readonly", false);
  setSelectValue("#frmMaLoi", "");
  $("#frmThoiGian").val("");
  setSelectValue("#frmTrangThai", "Chưa nộp phạt");
  $("#frmDiaDiem").val("");
  $("#autoFillBox").html('<span class="text-muted fst-italic">Nhập biển số hợp lệ để hiển thị thông tin...</span>');
  $("#btnSubmitForm").html('<i class="fa-solid fa-save me-1"></i> Lưu Hồ Sơ');
  console.log("✓ Form reset complete");
}

function editHs(data) {
  console.log("✏️ editHs() called with data:", data);
  $("#modalTitle").html('<i class="fa-solid fa-pen-to-square me-2"></i>Cập Nhật Hồ Sơ: ' + data.MaHoSo);
  $("#frmAction").val("edit");
  $("#frmMaHoSo").val(data.MaHoSo);

  // Disable bienSoXe (readonly, cannot edit vehicle plate)
  $("#frmBienSoXe").val(data.BienSoXe).trigger("change");
  $("#frmBienSoXe").prop("readonly", true).prop("disabled", true).css("pointerEvents", "none").css("backgroundColor", "#e9ecef");

  console.log("Setting MaLoi to:", data.MaLoi);
  setSelectValue("#frmMaLoi", data.MaLoi);
  console.log("After setSelectValue, #frmMaLoi value is:", $("#frmMaLoi").val());

  $("#frmDiaDiem").val(data.DiaDiemViPham);

  console.log("Setting TrangThai to:", data.TrangThai);
  setSelectValue("#frmTrangThai", data.TrangThai);
  console.log("After setSelectValue, #frmTrangThai value is:", $("#frmTrangThai").val());

  if (data.ThoiGianViPham) {
    let parts = data.ThoiGianViPham.split(/[- :]/);
    if (parts.length >= 5) {
      let formattedDate = `${parts[0]}-${parts[1]}-${parts[2]}T${parts[3]}:${parts[4]}`;
      $("#frmThoiGian").val(formattedDate);
    }
  }

  $("#btnSubmitForm").html('<i class="fa-solid fa-check me-1"></i> Cập Nhật');
  $("#viPhamModal").modal("show");
  console.log("✓ Edit form loaded");
}

function showToast(message, type = "success") {
  const bgClass = type === "success" ? "text-bg-success" : "text-bg-danger";
  const icon = type === "success" ? "fa-circle-check" : "fa-circle-xmark";

  const toastHtml = `
        <div class="toast align-items-center ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-bold">
                    <i class="fa-solid ${icon} me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

  $("#toastContainer").append(toastHtml);
  let toastElement = $("#toastContainer .toast").last()[0];
  let bsToast = new bootstrap.Toast(toastElement, { delay: 3000 });
  bsToast.show();
  toastElement.addEventListener("hidden.bs.toast", function () {
    $(this).remove();
  });
}

function deleteHs(maHoSo) {
  if (confirm("Bạn có chắc muốn xóa hồ sơ " + maHoSo + " không?")) {
    console.log("=== DELETE REQUEST ===");
    console.log("MaHoSo to delete:", maHoSo);

    $.get(
      "manage.php",
      { ajax_delete: maHoSo },
      function (res) {
        console.log("=== DELETE RESPONSE ===");
        console.log("Response:", res);

        if (res.success) {
          showToast(res.msg, "success");
          $("#tableContent").load("manage.php #tableContent > *");
        } else {
          showToast(res.msg, "danger");
        }
      },
      "json",
    ).fail(function () {
      console.error("Delete failed!");
      showToast("Lỗi hệ thống khi xóa! Kiểm tra Console.", "danger");
    });
  }
}

$(document).ready(function () {
  let focusTimer;

  console.log("✓ Document Ready - All event listeners registering now");
  console.log("Form elements found:", $("#viPhamForm").length);
  console.log("Modal elements found:", $("#viPhamModal").length);

  // Event listener cho MaLoi
  $("#frmMaLoi").on("change", function () {
    let selectedValue = $(this).val();
    let selectedText = $(this).find("option:selected").text();
    console.log("🔧 MaLoi changed - Value: '" + selectedValue + "' | Text: '" + selectedText + "'");
  });

  // Event listener cho TrangThai
  $("#frmTrangThai").on("change", function () {
    let selectedStatus = $(this).val();
    console.log("🔧 TrangThai changed - Value: '" + selectedStatus + "'");
  });

  // Event listener cho BienSoXe
  $("#frmBienSoXe").on("change", function () {
    let plate = $(this).val();
    console.log("🔧 BienSoXe changed - Value: '" + plate + "'");
  });

  // MAIN FORM SUBMIT HANDLER
  $("#viPhamForm").on("submit", function (e) {
    e.preventDefault();
    console.log("📤 Form submit event triggered!");

    let formData = $(this).serialize() + "&ajax_action=" + $("#frmAction").val();

    console.log("=== SUBMIT FORM ===");
    console.log("Action:", $("#frmAction").val());
    console.log("Form Data:", formData);
    console.log("MaLoi value:", $("#frmMaLoi").val());
    console.log("TrangThai value:", $("#frmTrangThai").val());

    $.post(
      "manage.php",
      formData,
      function (res) {
        console.log("=== SERVER RESPONSE ===");
        console.log("Response:", res);

        if (res.debug) {
          console.log("=== DEBUG LOG FROM PHP ===");
          res.debug.forEach((log) => console.log(log));
        }

        if (res.success) {
          $("#viPhamModal").modal("hide");
          showToast(res.msg, "success");
          $("#tableContent").load("manage.php #tableContent > *");
        } else {
          showToast(res.msg, "danger");
        }
      },
      "json",
    ).fail(function (xhr) {
      console.error("=== AJAX ERROR ===");
      console.error("Status:", xhr.status);
      console.error("Response Text:", xhr.responseText);
      showToast("Đã có lỗi xảy ra! Kiểm tra Console (F12) để xem chi tiết.", "danger");
    });

    return false;
  });

  // Auto-suggest listener
  $("#frmBienSoXe").on("keyup", function () {
    var keyword = $(this).val();
    if (keyword.length >= 2) {
      $.ajax({
        url: "../ajax/auto_suggest.php",
        method: "POST",
        data: { keyword: keyword },
        success: function (response) {
          $("#viPhamSuggest").html(response).removeClass("d-none");
        },
      });
    } else {
      $("#viPhamSuggest").addClass("d-none");
    }
  });

  $(document).on("click", "#viPhamSuggest .suggest-item", function () {
    let selectedPlate = $(this).text();
    $("#frmBienSoXe").val(selectedPlate);
    $("#viPhamSuggest").addClass("d-none");
    $("#frmBienSoXe").trigger("change");
  });

  // Auto-fill vehicle info
  $("#frmBienSoXe").on("change blur", function () {
    var plate = $(this).val();
    clearTimeout(focusTimer);
    focusTimer = setTimeout(function () {
      if (plate.length >= 3) {
        $.ajax({
          url: "../ajax/get_vehicle_info.php",
          method: "POST",
          dataType: "json",
          data: { plate: plate },
          success: function (res) {
            if (res.success) {
              let text = `<b class="text-success">${res.data.HoTen}</b> <br> <i class="fa-solid fa-address-card"></i> ${res.data.CCCD} <br> <i class="fa-solid fa-phone"></i> ${res.data.SoDienThoai} <br> <i class="fa-solid fa-car"></i> Mẫu: ${res.data.LoaiXe} | Nhãn: ${res.data.NhanHieu}`;
              $("#autoFillBox").html(text);
              $("#frmBienSoXe").removeClass("is-invalid").addClass("is-valid");
            } else {
              $("#autoFillBox").html('<span class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Không tìm thấy xe này trong hệ thống.</span>');
              $("#frmBienSoXe").removeClass("is-valid").addClass("is-invalid");
            }
          },
        });
      } else {
        $("#autoFillBox").html('<span class="text-muted fst-italic">Nhập biển số hợp lệ để hiển thị thông tin...</span>');
        $("#frmBienSoXe").removeClass("is-invalid is-valid");
      }
    }, 500);
  });

  console.log("✓ All event listeners registered successfully!");
});
