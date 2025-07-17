(function ($) {
    'use strict';



    //
    app.createSection({
        section_title: "Empleados",
        section_title_singular: "Empleado",
        scripts_path: "/app/employees",
        modalAddHtmlName: "add-record.html",
        endpoint_url: app.admin_url + "/employees",
        redirectAfterAdd: true,
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"data" : function(obj){
                    return obj.job_title + " - " + obj.name;
                }},
                {"name": "email", "data" : "email"},                
                {"name" : "departamento", "data" : "departamento"},
                {"data" : function(obj){
                    return obj.phone_cc + " " + obj.phone_number;
                }},
                {"data" : function(obj){
                    return (obj.commission_rate) ? "Comision: " + obj.commission_rate + "%": "";
                }},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        var data_info = JSON.stringify(obj);
                        //
                        var str_btns = "<div class='text-center'>";
                        var edit_url = "/admin/employees/" + obj.id + "/edit";
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
            loadSelectAjax({
                id: "#job_title_id",
                url: app.admin_url + "/job-titles/list",
                parseFields: function(item){
                    return item.name;
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                enable: true
            });

            //
            loadSelectAjax({
                id: "#departamento_id",
                url: app.admin_url + "/departments/list",
                parseFields: function(item){
                    return item.departamento;
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                enable: true
            });

            //
            $('#active').attr("checked", true);


            //
            loadSelectAjax({
                id: "#phone_country_id",
                url: app.public_url + "/paises/list",
                parseFields: function(item){
                    return "+" + item.phone_cc + " (" + item.abreviado + ")";
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                default_value: app.ID_PAIS_EU,
                enable: true
            });


            //
            setTimeout(function(){
                //
                $('#email, #phone_number')
                    .removeAttr("readonly")
                    
            }, 500);


            // def focus
            $("#name").focus();
        },
        onGridReady: function(opts){
            /**/
        },
        onSectionReady: function(opts){
            //
        }
    });




})(jQuery);