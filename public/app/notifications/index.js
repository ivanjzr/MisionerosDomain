(function ($) {
    'use strict';



    /*
    *
    * SECCION BLOG CATEGORIES
    *
    * */
    app.createSection({
        section_title: "Notificaciones",
        section_title_singular: "Notificacion",
        scripts_path: "/app/notifications",
        endpoint_url: app.admin_url + "/notifications",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name": "title", "data" : "title"},
                {"name": "message", "data" : "message"},
                {"data" : function(obj){
                    if ( obj.send_type === "c" ){
                        return "Customers";
                    } else if ( obj.send_type === "s" ){
                        return "Stores";
                    }
                }},
                {"data" : function(obj){ return fmtDateSpanish(obj.datetime_created.date); }},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";

                        //
                        str_btns += " <a href='#!' class='btn btn-sm btn-info btn-view-dismissed mb-1' data-info='"+data_info+"'><i class='fas fa-list-alt'></i> Dismissed <strong>(" + obj.cant_dismissed + ")</strong></a> ";
                        str_btns += "<br />";
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0, 4, 6],"orderable": false },
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
        },
        /*
        * SOLO EDIT MODE
        * */
        onEditReady: function(section_data){

            //
            $("#title").val(section_data.title);
            $('#message').val(section_data.message);
            $('#send_type').val(section_data.send_type);

            //
            if (section_data.active){
                $('#active').attr("checked", true);
            } else {
                $('#active').attr("checked", false);
            }


            if ( section_data.send_type === "c" ){
                $('#send_type_customers').attr("checked", true);
            } else if ( section_data.send_type === "s" ){
                $('#send_type_stores').attr("checked", true);
            }


        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){

            // def focus
            $("#title").focus();

        },
        onSectionReady: function(opts){
            //
        },
        onGridReady: function(opts){


            //
            $(".btn-view-dismissed").click(function(e) {
                e.preventDefault();



                var record_info = $(this).data("info");
                //console.log(record_info); return;

                //
                preload(".section-preloader, .overlay", true);
                disable_btns();

                //
                loadModalV2({
                    id: "modal-update-status",
                    modal_size: "lg",
                    data: record_info,
                    html_tmpl_url: "/app/notifications/modal-dismissed/index.html?v=1.0",
                    js_handler_url: "/app/notifications/modal-dismissed/index.js?v=1.0",
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        enable_btns();
                    }
                });

            });



        }
    });




})(jQuery);