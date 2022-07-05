let uploadList = [], uploadCount = 0, uploadQue = 0;

function selectFiles(e){
    let startIndex = 0;
    let fileList = [];

    uploadQue = 0;
    uploadCount = 0;

    for (let i = 0; i < e.target.files.length; i++) {
        let file = e.target.files[i];
        let blob = URL.createObjectURL(file);
        let name = file.name;
        let size = file.size;
        let ext = name.substring(name.lastIndexOf('.') + 1).toLowerCase();
        let gallery = {
            'index': (startIndex + i).toString(),
            'file': file,
            'name': name,
            'blob': blob,
            'size': size,
            'ext': ext
        };
        let insert = 1;
        for (let x = 0; x < fileList.length; x++){
            let oldFile = fileList[x];
            let oldName = oldFile.name;
            let oldSize = oldFile.size;

            if (oldName === name && oldSize === size){
                insert = 0;
                break;
            }
        }
        if (insert === 1) {
            if (size <= 10485760){
                uploadQue++;
            }
            fileList.push(gallery);
        }
    }

    uploadList = fileList;

    if (typeof uploadList !== 'undefined') {
        for (let i = 0; i < uploadList.length; i++) {
            let galleryItem = uploadList[i];
            let blob = galleryItem.blob;
            let size = galleryItem.size;
            let name = galleryItem.name;
            if (typeof blob === 'undefined') blob = "";
            if (typeof blob !== 'undefined'){
                if (blob !== ""){
                    if (size <= 10485760){
                        uploadFile(galleryItem);
                    } else {
                        alertError("Kesalahan", "Ukuran berkas: " + name + " melebihi 10MB");
                    }
                }
            }
        }
    }
}

function uploadFile(gallery){
    let file = gallery.file;
    let blob = gallery.blob;
    let size = gallery.size;

    let formData = new FormData();
    let id = $("#edit-id").val();
    let code = $("#edit-code").val();

    if (typeof file === "undefined") {
        file = "";
    }
    formData.append("action", "add");
    formData.append("id", id);
    formData.append("code", code);
    formData.append("path", blob);
    formData.append("size", size);
    formData.append("file", file);

    let apiURL = baseURL + viewController + "/" + viewAction;

    let elProgressFile = $('#progress-file');
    let elProgressBar = $('#progress-bar');

    $.ajax({
        url: apiURL,
        method: 'POST',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        xhr: function() {
            elProgressFile.show();
            let xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(e) {
                if (file !== ""){
                    let percent = Math.round((e.loaded / e.total) * 100);
                    elProgressBar.show()
                        .attr('aria-valuenow', percent)
                        .css('width', percent + '%');
                }
            });
            return xhr;
        },
        success: function (result) {
            if (typeof result !== "undefined") {
                uploadCount++;
                console.log(uploadCount + ":" + uploadQue);
                console.log(result);
                if (uploadCount === uploadQue){
                    location.href = baseURL + viewController + "/" + viewAction;
                }
            }
        },
    });
}

$(function () {
    $('#progress-file').hide();
    $(document).on('change', '#add-files', function(e) {
        selectFiles(e);
    });
    $(document).on('change', '#filter-type', function() {
        const id = $(this).val();
        location.href = baseURL + viewController + "/" + viewAction + "/" + id;
    });
});
