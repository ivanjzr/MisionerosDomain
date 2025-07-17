(function ($) {
    'use strict';



    

    //
    app.createSection({
        section_title: "Puntos de venta",
        section_title_singular: "Punto de venta",
        scripts_path: "/app/pos_list",
        modalAddHtmlName: "add-record.html",
        endpoint_url: app.admin_url + "/pos/list",
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name": "id", "data" : "name"},
                {"name" : "location_name", "data" : "location_name"},
                {"name": "active", "data" : function(obj){ return fmtActiveV2(obj.active, true); }},
                {"data" : function(obj){
                        //
                        //var newObject = jQuery.extend(true, {}, obj); newObject.description = null;
                        var data_info = JSON.stringify({});
                        //
                        var str_btns = "<div class='text-center'>";
                        var edit_url = "/admin/pos/list/" + obj.id + "/edit";
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
                { "targets": [0, 2, 3, 4, 5],"orderable": false },
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
            loadSelectAjax({
                id: "#location_id",
                url: app.admin_url + "/locations/list-available",
                parseFields: function(item){
                    return item.name;
                },
                prependEmptyOption: true,
                emptyOptionText: "--select",
                saveValue: true,
                enable: true,
            });



            //$(".sucursal_name").text($("#sucursal_name").val());


            // def focus
            $("#name").focus();
        },
        onGridReady: function(opts){

            

        },
        onSectionReady: function(opts){


            // 
            loadSelectAjax({
                id: "#filter_location_id",
                url: app.admin_url + "/locations/list",
                parseFields: function(item){
                    return item.name;
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


            //
            $(".caja_name").text($("#name").val());
        }
    });



    //
    function filterGrid(){
        //
        var filter_location_id = $("#filter_location_id").val();
        //
        $("#grid_section").DataTable().ajax.url(app.admin_url + "/pos/list?lid=" + filter_location_id);
        $("#grid_section").DataTable().ajax.reload();
    }


    //filterGrid();



    
    



})(jQuery);