// Default main js
$(document).ready(function () {
  // Logic auto suggest when typing in plateNumber input
  $("#plateNumber").on("keyup", function () {
    var keyword = $(this).val();
    if (keyword.length >= 2) {
      $.ajax({
        url: "/admin_vi_pham/ajax/auto_suggest.php",
        method: "POST",
        data: { keyword: keyword },
        success: function (response) {
          $("#plateSuggest").html(response).removeClass("d-none");
        },
      });
    } else {
      $("#plateSuggest").addClass("d-none");
    }
  });

  $(document).on("click", ".suggest-item", function () {
    $("#plateNumber").val($(this).text());
    $("#plateSuggest").addClass("d-none");
  });

  // Tra cứu (Fetch vehicle)
  $("#btnSearch").click(function () {
    var plate = $("#plateNumber").val();
    $.ajax({
      url: "/admin_vi_pham/ajax/fetch_vehicle.php",
      method: "POST",
      data: { plate: plate },
      success: function (response) {
        $("#resultArea").html(response);
      },
    });
  });
});
