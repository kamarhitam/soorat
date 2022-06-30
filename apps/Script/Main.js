const baseURL = $("meta[name=baseURL]").attr("content");
const viewController = $("meta[name=view-controller]").attr("content");
const viewAction = $("meta[name=view-action]").attr("content");
const viewId = $("meta[name=view-id]").attr("content");
const viewSub = $("meta[name=view-sub]").attr("content");

function alertSuccess(title, message) {
    $("#dialog-alert-success-title").html(title);
    $("#dialog-alert-success-text").html("<p>" + message + "</p>");
    $("#dialog-alert-success").modal("show");
}

function alertError(title, message) {
    $("#dialog-alert-error-title").html(title);
    $("#dialog-alert-error-text").html("<p>" + message + "</p>");
    $("#dialog-alert-error").modal("show");
}

function setInput(key, value){
    let el = $("[data-field='" + key +"']");
    if (el.prop("tagName") === "SELECT"){
        if (el.hasClass("select2bs4")){
            /*alert(el.attr("id") + ":" + key);*/
            if (el.hasClass("selectx")){
                /*alert(value);*/
                el.append('<option value="'+ value + '">' + value +'</option>');
                el.select2();
            }
            if (value !== "0")
                el.val(value).trigger('change');
        } else {
            el.val(value);
        }
    } else if (el.prop("tagName") === "IMG"){
        el.attr("src", value);
    } else {
        if (el.attr("type") === "radio"){
            $.each(el, function(i, el) {
                el.checked = el.value === value.toString();
            });
        } else {
            el.val(value);
        }
    }
    let elYear = $("[data-type='date-year']");
    if (elYear.attr("data-target") === key){
        let dates = value.split("-");
        elYear.val(dates[0]).trigger('change');
    }
    let elMonth = $("[data-type='date-month']");
    if (elMonth.attr("data-target") === key){
        let dates = value.split("-");
        elMonth.val(dates[1]).trigger('change');
    }
    let elDay = $("[data-type='date-day']");
    if (elDay.attr("data-target") === key){
        let dates = value.split("-");
        elDay.val(dates[2]).trigger('change');
    }
}

function onFillForm(el, dialog){
    let text = $(".item-data[data-id='" + $(el).attr("data-id") +"']");
    if (typeof text !== "undefined"){
        if (text.length > 0){
            let data = JSON.parse(text.val());
            if (typeof data !== "undefined"){
                $.each(data, function(akey, avalue) {
                    if (akey === "details"){
                        $.each(avalue, function(bkey, bvalue) {
                            $.each(bvalue, function(index, cvalue) {
                                setInput(bkey + '[' + index + ']', cvalue);
                            });
                        });
                    } else {
                        setInput(akey, avalue);
                    }
                });
            }
        }
    }
    dialog.modal("show");
}

$(function () {
    $('.text-editor').richText();
    $('.today-date').datetimepicker({
        format: 'DD-MM-YYYY'
    });
    $('.today-time').datetimepicker({
        format: 'HH:mm'
    });
    $('.select2bs4').select2({
        theme: 'bootstrap4',
        tags: true,
        tokenSeparators: [',', ' ']
    });

    $(".select2bs4").on("select2:select", function (evt) {
        const element = evt.params.data.element;
        const $element = $(element);
        $element.detach();
        $(this).append($element);
        $(this).trigger("change");
    });


    $('.datatable').DataTable({
        "oLanguage": {
            "sEmptyTable": "Tidak ada data",
            "sInfo": "Tampi _START_ ke _END_ dari _TOTAL_ baris",
            "sInfoEmpty": "Tidak menampilkan data",
            "sSearch": "Cari",
            "sLengthMenu": "Tampilkan _MENU_ baris",
            "oPaginate": {
                "sPrevious": "Sebelumnya",
                "sNext": "Berikutnya"
            },
        }
    });
    $(document).on("click", ".btn-item-edit", function(event) {
        event.preventDefault();
        let dialog = $("#dialog-edit");
        onFillForm(this, dialog);
    });
    $(document).on("click", ".btn-item-delete", function(event) {
        event.preventDefault();
        let dialog = $("#dialog-delete");
        let id = $(this).attr("data-id");
        $("[data-field='id']").val(id);
        dialog.modal("show");
    });

    let focusedElement;
    $(document).on("focus", ".gallery-path", function() {
        $(this).select();
    });
});
