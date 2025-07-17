(function ($) {
    'use strict';


    



    //
    app.createSection({
        section_title: "Negocios",
        section_title_singular: "negocio",
        modalSize: "md",
        scripts_path: "/app/stores",
        endpoint_url: app.admin_url + "/stores",
        editFieldName: "name",
        gridOptions:{
            columns: [
                {"data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){
                        //
                        var str_logo = "";
                        //
                        if (obj.biz_logo){
                            //
                            str_logo += "<div class='text-center'>";
                            str_logo += "<div class='rowImage' style='width:200px; height:100px; background-image:url("+obj.biz_logo + dynurl()+")'>&nbsp;</div>";
                            str_logo += "</div>";
                        }
                        //
                        var store_title = (obj.store_title) ? " - " + obj.store_title : " ---";
                        return str_logo + obj.company_name + store_title;
                    }},
                {"data" : function(obj){
                    var str_info = "";
                        str_info += "<h5 style='margin:0;padding:0;font-weight:bold;'>" + obj.name + "</h5>";
                        str_info += "<small>"+obj.email + "</small><br />";
                        str_info += "+" + obj.phone_cc + " " + obj.phone_number;
                        return str_info;
                }},
                {"data" : function(obj){
                        //
                        var str_suscripcion = "";
                        if (obj.periodicidad && obj.last_sale_datetime){
                            str_suscripcion += "<h4>" + ucFirst(obj.periodicidad) + "</h4>";
                            if (obj.has_valid_subs){
                                str_suscripcion += "<small class='badge badge-success'><span class='fa fa-trophy'></span> Valida </small>";
                            } else {
                                str_suscripcion += "<small class='badge badge-danger'> --no valida </small>";
                            }
                            //
                            return str_suscripcion;
                        }
                        //
                        return "-- sin suscripcion";
                    }},
                {"data" : function(obj){
                    //
                    var str_address = "<small>";
                        str_address += (obj.address) ? obj.address + "<br />" : "";
                        str_address += (obj.lat && obj.lng) ? "<strong style='color:#333;'>" + obj.lat + "/" + obj.lng + "</strong><br />" : "";
                        //
                        var country = (obj.country_code) ? "" + obj.country_code : "";
                        var state = (obj.state_code) ? " / " + obj.state_code : "";
                        var city = (obj.city_code) ? " / " + obj.city_code : "";
                        //
                        str_address += "<div style='color:#333;'>" + country + state + city + "</div>";
                        str_address += "</small>";
                    return str_address;
                }},
                {"name": "datetime_created", "data" : function(obj){ return fmtDateSpanish(obj.datetime_created.date, true); }},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        //
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-info btn-edit' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-primary btn-select-plan' data-info='"+data_info+"'><i class='fas fa-cash-register'></i></button> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-warning btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                {
                    "targets": [0, 2, 3, 4, 5, 6, 7, 8],
                    "orderable": false
                },
                {
                    "targets": "_all",
                    "searchable": false
                }
            ],
            hdrBtnsSearch: true,
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
            $('#name').val(section_data.name);
            $('#email').val(section_data.email);
            $('#phone_cc').val(section_data.phone_cc);
            $('#phone_number').val(section_data.phone_number);
            //
            if (section_data.active){
                $('#active').attr("checked", true);
            } else {
                $('#active').attr("checked", false);
            }

            //
            if ( section_data.thumb_img_url ){
                //
                $('#img_section_url').attr("src", section_data.thumb_img_url + dynurl());
                $('#img_section_url').attr("data-id", section_data.id);
                $('#img_section_url').css({
                    "width":200,
                    "height":150
                });
                $('#img_section_container').show();
            } else {
                $('#img_section_url').attr("src", null);
                $('#img_section_container').hide();
            }
        },
        /*
        * AMBOS ADD/EDIT MODE
        * */
        onAddEditReady: function(section_data){



            //
            loadSelectAjax({
                id: "#phone_country_id",
                url: app.public_url + "/paises/list",
                parseFields: function(item){
                    return "+" + item.phone_cc + " (" + item.abreviado + ")";
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                default_value: ((section_data && section_data.phone_country_id) ? section_data.phone_country_id : false),
                enable: true
            });


            // def focus
            $("#name").focus();

        },
        onGridReady: function(opts){



            //
            $('.btn-select-plan').click(function(e) {
                e.preventDefault();

                //
                var data_info = $(this).data("info");
                //console.log(data_info);

                disable_btns();

                //
                loadModalV2({
                    id: "modal-sales",
                    modal_size: "xl",
                    data: data_info,
                    html_tmpl_url: opts.scripts_path + "/modal-ventas/index.html?v="+dynurl(),
                    js_handler_url: opts.scripts_path + "/modal-ventas/index.js?v="+dynurl(),
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        enable_btns();
                    }
                });

            });

        },
        onSectionReady: function(opts){
            //
        }
    });








})(jQuery);