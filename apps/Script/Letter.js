
function getCatForms(id){
    let dataVar = {
        "action": "get",
        "data": {id : id}
    };

    let url = baseURL + viewController + "/" + viewAction;

    $.ajax({
        "type": "POST",
        "url": url,
        "data": dataVar,
        "beforeSend": function() {
            $("#loading").css("visibility", "visible");
        },
        "success": function(result) {
            if (typeof result != 'undefined'){
                let data = result.data;
                if (typeof data != 'undefined'){
                    let rows = data.data;
                    console.log(data);
                    if (typeof rows != 'undefined'){
                        $(".chkForms").removeAttr("checked");
                        $.each(rows, function(key, value) {
                            let idInput = value.idinput;
                            let num = value.num;
                            let elCheck = $(".chkForms[data-id='" + idInput + "']");
                            let elNum = $(".txtNums[data-id='" + idInput + "']");
                            elCheck.attr("checked", "checked");
                            elNum.val(num);
                        });
                    }
                }
            }
        }
    });
}

$(function () {
    $(document).on("click", ".btn-item-form", function() {
        let dialog = $("#dialog-form");
        let id = $(this).attr("data-id");
        $("[data-field='id']").val(id);
        getCatForms(id);
        dialog.modal("show");
    });
});
