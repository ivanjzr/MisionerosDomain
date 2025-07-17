(function ($) {
    'use strict';




    app.parseHrsMins = function(servicio_duracion_minutos){
        var minutos = parseInt(servicio_duracion_minutos);
        
        // Si no hay valor o es cero
        if (!minutos) return "0 minutos";
        
        // Convertir a formato horas y minutos si supera 60 minutos
        if (minutos >= 60) {
            var horas = Math.floor(minutos / 60);
            var minutosRestantes = minutos % 60;
            
            // Determinar el formato correcto según los valores
            if (minutosRestantes === 0) {
                // Solo horas exactas
                return horas + (horas === 1 ? " hora" : " horas");
            } else {
                // Horas y minutos
                return horas + (horas === 1 ? " hora " : " horas ") + 
                       minutosRestantes + (minutosRestantes === 1 ? " minuto" : " minutos");
            }
        } else {
            // Solo minutos (menos de una hora)
            return minutos + (minutos === 1 ? " minuto" : " minutos");
        }
    }

    

    //
    app.createSection({
        section_title: "Buses",
        section_title_singular: "Bus",
        scripts_path: "/app/buses",
        modalAddHtmlName: "add-record.html",
        endpoint_url: app.admin_url + "/buses",
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},                
                {"name" : "id", "data" : "id"},
                {"name" : "bus_code", "data" : "bus_code"},
                {"data" : function(obj){
                        // 
                        var str_description = (obj.description) ? "<br /><small style='color:green;'>" + obj.description + "</small>" : "";
                        return obj.nombre + str_description;
                    }},
                {"data" : function(obj){
                    return "<small>" + obj.make  + " - " + obj.model + "</small>";
                }},
                {"name" : "year", "data" : "year"},
                {"data" : function(obj){
                        return "<strong style=''> " + fmtAmount(obj.precio) + "</strong>";
                    }},
                {"name": "active", "data" : function(obj){ return fmtActive(obj.active, true); }},
                {"data" : function(obj){
                        //
                        //var newObject = jQuery.extend(true, {}, obj); newObject.description = null;
                        var data_info = JSON.stringify({});
                        //
                        var str_btns = "<div class='text-center'>";
                        var edit_url = "/admin/buses/" + obj.id + "/edit";
                        //
                        str_btns += " <a href='"+edit_url+"' class='btn btn-sm btn-flat btn-info' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></a> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
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
            deferLoading: true,
            hdrBtnsSearch: true,
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
            selectLoad2({
                id: "#tipo_bus",
                data: [
                    {id:"p", name:"Regular"},
                    {id:"s", name:"Double Decker"},
                ],
                emptyOptionText: "--select",
                enable: true,
                saveValue: true,
                onChange: function(value) {
                    //console.log('onChange:', value);
                    loadBusType();
                },
                onReady: function(value, items) {
                    //console.log('onReady:', value, items);
                    loadBusType();
                }
            });



            //
            loadSelectAjax({
                id: "#make_id",
                url: app.admin_url + "/buses/makes/list",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                enable: true,
                onChange: function(){
                    //
                    $("#model_id")
                        .html("")
                        .attr("disabled", true)
                        .append("<option>--</option>");
                    //
                    var make_id = $("#make_id").val();
                    if (make_id){
                        //
                        loadSelectAjax({
                            id: "#model_id",
                            url: app.admin_url + "/buses/models/list?mid="+make_id,
                            parseFields: function(item){
                                return item.nombre;
                            },
                            prependEmptyOption: true,
                            emptyOptionText: "--select",
                            enable: true
                        });
                    }
                    
                }
            });

            //
            $(".sucursal_name").text($("#sucursal_name").val());


            



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
            loadSelectAjax({
                id: "#filter_make_id",
                url: app.admin_url + "/buses/makes/list",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                emptyOptionText: "--all",
                saveValue: true,
                enable: true,
                onChange: function(){
                    filterGrid()
                },
                onReady: function(){
                    filterGrid()
                }
            });


            //
            $(".sucursal_name").text($("#sucursal_name").val());
        }
    });



    //
    function filterGrid(){
        //
        var filter_make_id = $("#filter_make_id").val();
        //
        $("#grid_section").DataTable().ajax.url(app.admin_url + "/buses?mkid=" + filter_make_id);
        $("#grid_section").DataTable().ajax.reload();
    }


    //filterGrid();



    //
    $("#form_upload").submit(function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        //
        if ( $("#form_upload").valid() ) {
            //
            $("#form_upload").ajaxSubmit({
                url: app.admin_url + "/buses/upload",
                beforeSubmit: function(arr){
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay", true);
                },
                success: function(send_response){
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //
                    if (send_response && send_response.id){
                        app.Toast.fire({ icon: 'success', title: "File Uploaded Ok" });
                        $("#grid_section").DataTable().ajax.reload();
                    }
                    //
                    else if (send_response.error){
                        app.Toast.fire({ icon: 'error', title: send_response.error });
                    }
                    //
                    else {
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                },
                error: function(response_error){
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });
        }
    });





})(jQuery);