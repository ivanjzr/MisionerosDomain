(function ($) {
    'use strict';



    /*
    *
    * SECCION POSTS
    *
    * */
    app.createSection({
        section_title: "Posts",
        section_title_singular: "Post",
        scripts_path: "/app/blog",
        modalAddHtmlName: "add-record.html",
        endpoint_url: app.admin_url + "/blog/posts",
        modalSize: "xl",
        redirectAfterAdd: true,
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name": "nombre", "data" : function(obj){
                        //
                        var str_info = "";
                        //
                        str_info += "<h3 style='padding:0;margin:0;'>" + obj.nombre + "</h3>";
                        str_info += "<div style='color:blue;'>" + obj.category + "</div>";
                        str_info += "<small>/" + obj.url + "</small>";
                        //
                        return str_info;
                    }},
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
                {"data" : function(obj){
                    return obj.short_content;
                    }},
                {"name": "datetime_created", "data" : function(obj){ return fmtDateSpanish(obj.datetime_created.date, true); }},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        var edit_url = "/admin/blog/posts/" + obj.id + "/edit";
                        //
                        //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-primary btn-view' data-info='"+data_info+"'><i class='fas fa-folder'></i></button> ";
                        str_btns += " <a href='"+edit_url+"' class='btn btn-sm btn-flat btn-info' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></a> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            deferLoading: true,
            columnDefs: [
                { "targets": [0, 3, 4, 6],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){

            //
            $('#active').attr("checked", true);

            //
            $('#nombre').keyup(function(e) {
                //
                var nombre = $(this).val();
                $("#url").val(convertToUrl(nombre));
            });


            //
            var filter_category_id = $("#filter_category_id").val();
            //
            loadSelectAjax({
                id: "#category_id",
                url: app.admin_url + "/cat/blog-categories/list",
                parseFields: function(item){
                    return item.category;
                },
                default_value: filter_category_id,
                prependEmptyOption: true,
                emptyOptionText: "--select",
                enable: true
            });



            // def focus
            $("#nombre").focus();
        },
        onGridReady: function(opts){

            //
            $(".btn-view-download-file").click(function(e){
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
            function filterGrid(){
                //
                var filter_category_id = $("#filter_category_id").val();
                //
                $("#grid_section").DataTable().ajax.url(app.admin_url + "/blog/posts?filter_category_id=" + filter_category_id);
                $("#grid_section").DataTable().ajax.reload();
            }


            //
            loadSelectAjax({
                id: "#filter_category_id",
                url: app.admin_url + "/cat/blog-categories/list",
                parseFields: function(item){
                    return item.category;
                },
                prependEmptyOption: true,
                emptyOptionText: "--todos",
                saveValue: true,
                enable: true,
                onChange: function(){
                    filterGrid()
                },
                onReady: function(){
                    filterGrid()
                }
            });


        }
    });




})(jQuery);