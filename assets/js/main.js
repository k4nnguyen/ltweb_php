// Default main js
$(document).ready(function () {
  // Logic auto suggest when typing in plateNumber input
  $("#plateNumber").on("keyup", function () {
    var keyword = $(this).val().trim();
    if (keyword.length >= 2) {
      $.ajax({
        url: "/admin_vi_pham/ajax/auto_suggest.php",
        method: "POST",
        data: { keyword: keyword },
        success: function (response) {
          $("#plateSuggest").html(response).removeClass("d-none");
        },
        error: function () {
          $("#plateSuggest").html("<div class='p-3 text-danger text-center'>Lỗi tải gợi ý</div>").removeClass("d-none");
        },
      });
    } else {
      $("#plateSuggest").addClass("d-none").html("");
    }
  });

  // Ẩn suggest khi click ra ngoài
  $(document).on("click", function (e) {
    if (!$(e.target).closest("#plateNumber").length && !$(e.target).closest("#plateSuggest").length) {
      $("#plateSuggest").addClass("d-none");
    }
  });

  $(document).on("click", ".suggest-item", function () {
    // Lấy biển số từ data-plate attribute (clean trim)
    var plateNumber = $(this).data("plate").trim();
    $("#plateNumber").val(plateNumber);
    $("#plateSuggest").addClass("d-none").html("");
  });

  // Tra cứu (Fetch vehicle)
  $("#btnSearch").click(function () {
    var plate = $("#plateNumber").val().trim();
    if (!plate) {
      alert("Vui lòng nhập biển số xe!");
      return;
    }
    $.ajax({
      url: "/admin_vi_pham/ajax/fetch_vehicle.php",
      method: "POST",
      data: { plate: plate },
      success: function (response) {
        $("#resultArea").html(response);
      },
      error: function () {
        $("#resultArea").html("<div class='alert alert-danger'>Lỗi khi tìm kiếm!</div>");
      },
    });
  });
});
