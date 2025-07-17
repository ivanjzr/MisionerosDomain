(function ($) {
    'use strict';



    /*
    *
    * SECCION EVENTOS
    *
    * */
    app.createSection({
        section_title: "Eventos",
        section_title_singular: "Evento",
        scripts_path: "/app/events",
        modalAddHtmlName: "add-record.html",
        endpoint_url: app.admin_url + "/events",
        redirectAfterAdd: true,
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name": "title", "data" : "title"},
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
                        //
                        var str_fechas = "<ul>";
                        //
                        if (obj.fechas && obj.fechas.length){
                            $.each(obj.fechas, function(idx, item){
                                //console.log(idx, item.value);
                                str_fechas += "<li class='text-li-sm'>" + fmtDateSpanish(item.event_date.date) + "</li>";
                            });
                        }
                        //
                        str_fechas +="</ul>";
                        //
                        return str_fechas;
                    }},
                {"data" : function(obj){ return safeNulValue(obj.description); }},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        var edit_url = "/admin/events/" + obj.id + "/edit";
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
            columnDefs: [
                { "targets": [0, 4],"orderable": false },
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
            $('#title').keyup(function(e) {
                //
                var title = $(this).val();
                $("#url").val(convertToUrl(title));
            });


            // def focus
            $("#title").focus();
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
                        $('#modal-title').text("Imagen " + data_info.title);
                        $("#preview_img_id").attr("src", data_info.orig_img_url);
                    }
                });

            });

        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);