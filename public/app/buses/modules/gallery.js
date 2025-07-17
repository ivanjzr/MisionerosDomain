define(function(){
    function moduleReady(section_data){
        //console.log(section_data);


        //
        app.createSection({
            gridId: "#grid_bus_images",
            section_title: "Gallery",
            data: section_data,
            section_title_singular: "Gallery",
            scripts_path: "/app/buses/gallery",
            endpoint_url: section_data.opts.endpoint_url + "/" + section_data.id + "/gallery",
            gridOptions:{
                columns: [
                    {"visible": false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name" : "id", "data" : "id"},
                    {"visible": false, "name" : "bus_id", "data" : "bus_id"},
                    {"data" : function(obj){
                            //
                            if (obj.thumb_img_url){
                                //
                                var data_info = JSON.stringify(obj);
                                //
                                var str_info = "<div class='text-center'>";
                                str_info += "<div class='rowImage' style='width:200px; height:100px; background-image:url("+obj.thumb_img_url + dynurl()+")'>&nbsp;</div>";
                                str_info += "</div>";
                                str_info += " <br /><button type='button' class='btn btn-sm btn-flat btn-default btn-view-download-file' data-info='"+data_info+"'><i class='fa fa-eye'></i> Ver </button>";
                                return str_info;
                            } else {
                                return "";
                            }
                        }},
                    {"name": "datetime_created", "data" : function(obj){ return fmtDateSpanish(obj.datetime_created.date, true); }},
                    {"data": function(obj){
                            //
                            //var data_info = JSON.stringify(obj);
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                            str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                            //
                            str_btns += "</div>";
                            //
                            return str_btns;
                        }},
                ],
                columnDefs: [
                    { "targets": [0, 3, 5],"orderable": false },
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]],
            },
            onGridReady: function(opts){


                //
                $("#grid_bus_images .btn-view-download-file").click(function(e){
                    e.preventDefault();
                    //
                    var data_info = $(this).data("info");
                    //
                    loadModalV2({
                        id: "modal-view-download-image",
                        modal_size: "lg",
                        html_tmpl_url: "/app/common/preview-img.html?v=3",
                        onBeforeLoad: function(){
                            disable_btns();
                        },
                        onInit: function(){
                            //
                            enable_btns();
                            //
                            $('#modal-title').text("Imagen " + data_info.nombre);
                            $("#preview_img_id").attr("src", data_info.orig_img_url);
                        }
                    });

                });


            },
            onSectionReady: function(opts){


                //
                $('#form_gallery').validate();
                //
                $('#form_gallery').submit(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    //
                    if ( $('#form_gallery').valid() ) {
                        //
                        $('#form_gallery').ajaxSubmit({
                            url: section_data.opts.endpoint_url + "/" + section_data.id + "/gallery",
                            beforeSubmit: function(arr){
                                disable_btns();
                            },
                            success: function(response){
                                //
                                enable_btns();
                                //
                                if (response && response.id){
                                    //
                                    app.Toast.fire({ icon: 'success', title: "Record added succesfully" });
                                    //
                                    $("#grid_bus_images").DataTable().ajax.reload();
                                    $("#gallery_img_section").val("");
                                    //
                                    section_data.opts.loadData();
                                }
                                //
                                else if (response.error){
                                    app.Toast.fire({ icon: 'error', title: response.error});
                                }
                                //
                                else {
                                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                                }
                            },
                            error: function(response){
                                enable_btns();
                                //
                                app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                            }
                        });

                    }
                });



                //
                $('#btnReloadGallery').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    $("#grid_bus_images").DataTable().ajax.reload();
                });


            }
        });



    }
    return {init: moduleReady}
});