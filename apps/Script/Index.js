let baseURL = $("meta[name=baseURL]").attr("content");
$(function () {
    $("form").submit(function (event) {
        event.preventDefault();
        const formData = {
            action: "contact",
            name: $("#name").val(),
            email: $("#email").val(),
            subject: $("#subject").val(),
            message: $("#message").val(),
            unique: $("#unique").val(),
        };
        $.ajax({
            type: "POST",
            url: baseURL,
            data: formData,
            dataType: "json",
            encode: true,
        }).done(function (data) {
            if (typeof data !== "undefined") {
                const success = data.success;
                if (typeof success !== "undefined") {
                    if (success === 1) {
                        $("#contact-alert").html('<div class="alert alert-success alert-dismissible"><span>Pesan Anda sudah terkirim</span></div>')
                    }
                }
            }
        });
    });
});
