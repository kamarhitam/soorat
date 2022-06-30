
$(function () {
    $(document).on("click", "#img-profile", function() {
        $("#file").trigger('click');
    });

    $('#file').change(function() {
        let url = window.URL.createObjectURL(this.files[0]);
        $('#img-profile').attr('src', url);
    });

    $(document).on("click", "#user-add-image", function() {
        $("#add-image").trigger('click');
    });

    $('#add-image').change(function() {
        let url = window.URL.createObjectURL(this.files[0]);
        $('#user-add-image').attr('src', url);
    });

    $(document).on("click", "#user-edit-image", function() {
        $("#edit-image").trigger('click');
    });

    $('#edit-image').change(function() {
        let url = window.URL.createObjectURL(this.files[0]);
        $('#user-edit-image').attr('src', url);
    });
});
