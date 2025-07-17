define(function(){
    function moduleReady(modal, data_info){
        //console.log(modal, data_info);


        //
        $("#modal-title").text("Imagenes " + data_info.nombre);

        //
        $("#btnSelectImage").click(function(){
            //
            var file_url = $("#file_url").val();
            //
            if (file_url){
                //
                var img_width = $("#img_width").val();
                img_width = (img_width) ? "width:" + img_width : "";
                //
                var img_element = "<img src='"+file_url+"' style='"+ img_width+ "' alt='' />";
                appendToSummernote("#contenido", img_element);
                //
                $("#select-image").find('.modal').modal("hide");
            }
        });


        //
        app.loadFolderFiles = function(info){
            //console.log(info);
            //
            $.buildTable({
                url: app.admin_url + "/docs-manager/folders/" + info.id + "/files",
                tableItemsName: "#table-files",
                data: info,
                onSelectCheckboxItem: function(self, info){
                    //console.log(self, info);
                    //
                    $("#file_url").val(info.app_url + "/" + info.file_url);
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







    }
    return {init: moduleReady}
});






