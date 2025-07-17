(function ($) {
    'use strict';



    //
    app.createSection({
        section_title: "Sucursales",
        section_title_singular: "Sucursal",
        scripts_path: "/app/sucursales",
        modalAddHtmlName: "add-record.html",
        endpoint_url: app.admin_url + "/sucursales",
        redirectAfterAdd: true,
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name" : "name", "data" : "name"},
                {"data" : function(obj){
                        return obj.address + ", " + obj.ciudad + ", " + obj.estado;
                    }},
                {"data" : function(obj){
                        return obj.phone_cc + " " + obj.phone_number;
                    }},
                {"name": "email", "data" : "email"},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        var edit_url = "/admin/sucursales/" + obj.id + "/edit";
                        //
                        str_btns += " <a href='"+edit_url+"' class='btn btn-sm btn-flat btn-info' data-info='"+data_info+"'><i class='fas fa-pencil-alt'></i></a> ";
                        str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='"+obj.id+"'><i class='fas fa-trash'></i></button>";
                        //
                        str_btns += "</div>";
                        //
                        return str_btns;
                    }},
            ],
            columnDefs: [
                { "targets": [0, 3, 5, 7],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            order: [[ 1, "desc" ]]
        },
        /*
        * SOLO ADD MODE
        * */
        onAddReady: function(data){


            //
            loadSelectAjax({
                id: "#estado_id",
                url: app.admin_url + "/sys/states",
                parseFields: function(item){
                    return item.nombre;
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                enable: true,
                onChange: function(){
                    //
                    $("#ciudad_id")
                        .html("")
                        .attr("disabled", true)
                        .append("<option>--</option>");
                    //
                    var estado_id = $("#estado_id").val();
                    if (estado_id){
                        //
                        loadSelectAjax({
                            id: "#ciudad_id",
                            url: app.admin_url + "/sys/states/" + estado_id + "/cities",
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
            $('#active').attr("checked", true);


            // def focus
            $("#nombre").focus();
        },
        onGridReady: function(opts){
            /**/
        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);