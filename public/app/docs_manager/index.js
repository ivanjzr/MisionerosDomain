(function ($) {
    'use strict';



    //
    var myDropzone;
    // DropzoneJS Demo Code Start
    Dropzone.autoDiscover = false


    //
    app.loadFolderFiles = function(info){
        //console.log(info);
        //
        $.buildTable({
            url: app.admin_url + "/docs-manager/folders/" + info.id + "/files",
            tableItemsName: "#table-files",
            addItemName: "#btnAddFile",
            modalSize: "xl",
            url_del: "/del",
            delFieldName: "file_path",
            data: info,
            addModalTitle: "Agregar Archivo en folder " + info.folder_name,
            addModalPath: "/app/docs_manager/modal-files/add-edit-record.html?v=" + dynurl(),
            addReadyEvt: function(evts, settings){

                // Get the template HTML and remove it from the doumenthe template HTML and remove it from the doument
                var previewNode = document.querySelector("#template")
                previewNode.id = ""
                var previewTemplate = previewNode.parentNode.innerHTML
                previewNode.parentNode.removeChild(previewNode)

                //
                myDropzone = new Dropzone("#modal-section", {
                    url: app.admin_url + "/docs-manager/folders/" + info.id + "/files",
                    thumbnailWidth: 80,
                    thumbnailHeight: 80,
                    parallelUploads: 20,
                    previewTemplate: previewTemplate,
                    autoQueue: false, // Make sure the files aren't queued until manually added
                    previewsContainer: "#previews", // Define the container to display the previews
                    clickable: ".fileinput-button" // Define the element that should be used as click trigger to select files.
                });
                //
                myDropzone.on("addedfile", function(file) {
                    // Hookup the start button
                    //console.log("addedfile");
                    file.previewElement.querySelector(".start").onclick = function() { myDropzone.enqueueFile(file) }
                })

                // Update the total progress bar
                myDropzone.on("totaluploadprogress", function(progress) {
                    //console.log("totaluploadprogress");
                    document.querySelector("#total-progress .progress-bar").style.width = progress + "%"
                })

                myDropzone.on("sending", function(file) {
                    //console.log("sending");
                    // Show the total progress bar when upload starts
                    document.querySelector("#total-progress").style.opacity = "1"
                    // And disable the start button
                    file.previewElement.querySelector(".start").setAttribute("disabled", "disabled")
                });

                // Hide the total progress bar when nothing's uploading anymore
                myDropzone.on("queuecomplete", function(progress) {
                    //
                    setTimeout(function(){
                        //
                        $(".dz-image-preview").fadeOut();
                        //
                        setTimeout(function(){
                            myDropzone.removeAllFiles(true);
                        }, 1000);
                    }, 1000);
                    //
                    evts.loadItems();
                    //
                    document.querySelector("#total-progress").style.opacity = "0"
                })

                // Setup the buttons for all transfers
                // The "add files" button doesn't need to be setup because the config
                // `clickable` has already been specified.
                document.querySelector("#actions .start").onclick = function() {
                    myDropzone.enqueueFiles(myDropzone.getFilesWithStatus(Dropzone.ADDED))
                }
                document.querySelector("#actions .cancel").onclick = function() {
                    myDropzone.removeAllFiles(true)
                }
                //
                myDropzone.on("error", function(file, response) {
                    //console.log(file, response);
                });
            },
            deleteConfirmText: function(info){
                return "Eliminar Archivo " + info.file_name;
            },
            onAfterDelete: function(evts, settings, row_el){
                row_el.fadeOut();
            },
            parseItem: function(index, settings, item){
                //
                var data = JSON.stringify(item);
                //
                var str_item = "<tr>";
                str_item += "<td>";
                str_item += "<input type='checkbox' class='styled-checkbox "+noid(settings.btnSelectCheckboxItem)+"' data-info='"+data+"'>";
                str_item += "</div>";
                str_item += "</td>";
                str_item += "<td class='mailbox-name'><a href='#'>" + item.file_name + "</a></td>";
                str_item += "<td class='mailbox-date'>" + item.file_size + "</td>";
                str_item += "<td class='mailbox-date'>";
                str_item += "&nbsp;<button type='button' class='btn btn-default btn-sm "+noid(settings.btnDeleteItem)+" float-right' data-info='"+data+"'>";
                str_item += "<i class='far fa-trash-alt'></i>";
                str_item += "</button>";
                str_item += "</td>";
                //
                str_item += "</tr>";
                //
                return str_item;
            },
            loadComplete: function(evts, settings){
                //console.log(settings, evts);

                //
                $("#folder-name").text(info.folder_name);
                $(".btns-content").show();


                //
                $("#btnRefreshFiles").unbind("click");
                $("#btnRefreshFiles").click(function(e) {
                    e.preventDefault();
                    //
                    evts.loadItems();
                });



                //
                $("#btnDelFiles").unbind("click");
                $("#btnDelFiles").click(function(e){
                    e.preventDefault();

                    //
                    if (confirm("Remove " + $(settings.btnSelectCheckboxItem).length + " elements?")){

                        //
                        var arr_elems = [];
                        var deleted_ids = '';

                        //
                        $(settings.btnSelectCheckboxItem).each(function(idx, item){

                            //
                            var data = $(this).data("info");
                            var row_el = $(item).parent().parent();
                            //console.log(row_el);

                            //
                            if ($(this).is(":checked")){
                                //
                                arr_elems.push(
                                    //
                                    $.ajax({
                                        type: "POST",
                                        url: app.admin_url + "/docs-manager/folders/" + info.id + "/files/del",
                                        data: $.param({
                                            file_path: data.file_path
                                        }),
                                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                        success: function (response) {
                                            //console.log(response.data);

                                            //
                                            if (response.success || response.file_path) {
                                                //
                                                console.log("registro con path " + response.file_path + " eliminado correctamente");
                                                //$('#grid_section').jqGrid('delRowData', id);
                                                //
                                                row_el.fadeOut();
                                                //
                                                deleted_ids += response.file_path + ', ';
                                            }

                                            //
                                            else if (response.error) {

                                                //
                                                console.log(response.error);
                                            }

                                        },
                                        error: function () {

                                            //
                                            console.log("No se pudo completar la operación. Verifica tu conexión o contacta al administrador al intentar eliminar el registro con path " + response.file_path);
                                        }
                                    })
                                );

                            }
                        });



                        //
                        preload(".section-preloader, .overlay", true);
                        disable_btns();
                        //
                        $.when.apply(null, arr_elems)
                            .done(function(a) {

                                //
                                preload(".section-preloader, .overlay");
                                enable_btns();

                                //
                                console.log('Registro(s) con id(s) ' + deleted_ids + ' eliminado(s) correctamente');

                                //
                                //evts.loadItems();
                            });
                    }
                });




                //Enable check and uncheck all functionality
                $('.checkbox-toggle').click(function () {
                    //
                    var clicks = $(this).data('clicks')
                    if (clicks) {
                        //Uncheck all checkboxes
                        $('.mailbox-messages td').removeClass('active');
                        $('.mailbox-messages input[type=\'checkbox\']').prop('checked', false);
                        $('.checkbox-toggle .far.fa-check-square').removeClass('fa-check-square').addClass('fa-square')
                    } else {
                        //Check all checkboxes
                        $('.mailbox-messages td').addClass('active');
                        $('.mailbox-messages input[type=\'checkbox\']').prop('checked', true)
                        $('.checkbox-toggle .far.fa-square').removeClass('fa-square').addClass('fa-check-square')
                    }
                    $(this).data('clicks', !clicks);
                })


            }
        });
    }





    //
    $.buildTable({
        //
        tableItemsName: "#table-folders",
        //
        onSelectItem: function(evts, info, item_el){
            //console.log(info);
            app.loadFolderFiles(info);
        },
        //
        url: app.admin_url + "/docs-manager/folders",
        url_read: "/list",
        url_del: "/del",
        addModalTitle: "Agregar Folder",
        addModalPath: "/app/docs_manager/modal-folders/add-edit-record.html?v=" + dynurl(),
        addReadyEvt: function(){
            //
            $("#folder_name").focus();
        },
        //
        editModalTitle: function(info){
            return "Editar Folder " + info.folder_name;
        },
        editModalPath: "/app/docs_manager/modal-folders/add-edit-record.html?v=" + dynurl(),
        editReadyEvt: function(data){
            //
            $("#folder_name")
                .val(data.folder_name)
                .focus();
            //
            $("#description").val(data.description);
        },
        deleteConfirmText: function(info){
            return "Eliminar Folder " + info.folder_name;
        },
        onAfterDelete: function(evts, settings, row_el){
            evts.loadItems();
        },
        onBeforeLoad: function(){
            //console.log("onBeforeLoad");
            $("#table-files").html("");
        },
        parseItem: function(index, settings, item){
            //
            var data = JSON.stringify(item);
            //
            var badge_info = "";
            if (item.qty_files){
                badge_info = "&nbsp;&nbsp;<span class='badge bg-info'>" + item.qty_files + "</span>";
            }
            //
            var str_item = "<li class='nav-item active'>";
            str_item += "<a href='#' class='nav-link "+noid(settings.btnSelectItem)+"' data-info='"+data+"'>";
            str_item += "<i class='fas fa-file-alt'></i> " + item.folder_name + badge_info;
            str_item += "&nbsp;<button type='button' class='btn btn-default btn-sm "+noid(settings.btnDeleteItem)+" float-right' data-info='"+data+"'>";
            str_item += "<i class='far fa-trash-alt'></i>";
            str_item += "</button>";
            str_item += "&nbsp;<button type='button' class='btn btn-default btn-sm "+noid(settings.btnEditItem)+" float-right' data-info='"+data+"'>";
            str_item += "<i class='far fa-edit'></i>";
            str_item += "</button>";
            str_item += "</a>";
            str_item += "</li>";
            //
            return str_item;
        },
        loadComplete: function(evts, settings){
            //
            if ($("#table-folders li.nav-item").length){
                //
                var item = $(settings.btnSelectItem)[0];
                $(item).addClass("active");
                var info = $(item).data("info");
                //
                app.loadFolderFiles(info);
            }
        }
    });






})(jQuery);