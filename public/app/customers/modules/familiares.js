define(function(){
    function moduleReady(section_data){
        console.log("----customer data: ", section_data);

        //
        app.createSection({
            gridId: "#grid_customer_relatives",
            section_title: "Familiares",
            data: section_data,
            section_title_singular: "Familiar",
            scripts_path: "/app/customers",
            endpoint_url: app.admin_url + "/customers/" + section_data.id + "/relatives",
            gridOptions:{
                columns: [
                    {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                    {"name": "id", "data" : "id"},
                    {"name": "name", "data" : "name"},
                    {"name" : "edad_years", "data" : function(obj){
                        //
                        return (obj.birth_date && obj.birth_date.date) ? moment(obj.birth_date.date).format('DD MMM YYYY')  + " / " + obj.edad_years : "--";
                    }},
                    {"name": "relative_type", "data" : "relative_type"},
                    {"name" : "datetime_created", "data" : function(obj){
                        return moment(obj.datetime_created.date).format('DD MMM YYYY hh:mm A');
                    }},
                    {"data": function(obj){
                            //
                            obj.customer_name = section_data.customer_name;
                            var data_info = JSON.stringify(obj);
                            //
                            var str_btns = "<div class='text-center'>";
                            //
                            str_btns += " <button type='button' class='btn btn-sm btn-outline-secondary btn-editar me-1' data-info='"+data_info+"' title='Editar'><i class='fas fa-edit'></i></button> ";
                            str_btns += " <button type='button' class='btn btn-sm btn-outline-danger btn-eliminar' data-id='"+obj.id+"' title='Eliminar'><i class='fas fa-trash'></i></button>";
                            //
                            str_btns += "</div>";
                            //
                            return str_btns;
                        }},
                ],
                deferLoading: true,
                columnDefs: [
                    { "targets": [0,2,3,4,5],"orderable": false },
                    { "targets": "_all", "searchable": false }
                ],
                order: [[ 1, "desc" ]],
            },
            onEditReady: function(record_data){
                //console.log("Loading patient data:", record_data);
            },
            onGridReady: function(opts){

                //
                $('.btn-editar').click(function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    //
                    let record_data = $(this).data("info");
                    console.log("Edit customer relatives:", record_data);
                    //
                    loadModalV2({
                        id: "modal-relative",
                        modal_size: "xl",
                        data: record_data,
                        html_tmpl_url: "/app/customers/modals/relatives/edit.html?v=" + dynurl(),
                        js_handler_url: "/app/customers/modals/relatives/edit.js?v=" + dynurl(),
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
            }
        });



        //
        $('#btnAddRelative').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();            
            //
            loadModalV2({
                id: "modal-relative",
                modal_size: "xl",
                data: section_data,
                html_tmpl_url: "/app/customers/modals/relatives/add.html?v=" + dynurl(),
                js_handler_url: "/app/customers/modals/relatives/add.js?v=" + dynurl(),
                onBeforeLoad: function(){
                    disable_btns();
                },
                onInit: function(){
                    enable_btns();
                }
            });
        });




        //
        app.filterCustomerRelatives = function(){
            //var filter_sale_type_id = $("#filter_sale_type_id").val();
            $("#grid_customer_relatives").DataTable().ajax.url(app.admin_url + "/customers/" + section_data.id + "/relatives");
            $("#grid_customer_relatives").DataTable().ajax.reload();
        }




        setTimeout(function(){
            app.filterCustomerRelatives();
        }, 1000);


   

        //
        $('#btnReloadFamiliares').click(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            //
            $("#grid_customer_relatives").DataTable().ajax.reload();
        });


    
    }
    return {init: moduleReady}
});