(function ($) {
    'use strict';

    //
    app.createSection({
        section_title: "Citas",
        section_title_singular: "Cita",
        scripts_path: "/app/appointments",
        modalAddHtmlName: "add-record.html",
        endpoint_url: app.admin_url + "/appointments",
        gridOptions:{
            columns: [
                {visible: false, "data" : function(obj){ return setCheckbox(obj.id); }},
                {"name" : "id", "data" : "id"},
                {"name" : "start_datetime", "data" : function(obj){ 
                    if (obj.start_datetime && obj.start_datetime.date) {
                        return moment(obj.start_datetime.date).format('DD/MM/YYYY');
                    }
                    return '-';
                }},
                {"name" : "start_datetime", "data" : function(obj){ 
                    if (obj.start_datetime && obj.start_datetime.date) {
                        return moment(obj.start_datetime.date).format('HH:mm');
                    }
                    return '-';
                }},
                {"name" : "customer_name", "data" : function(obj){ 
                    var customer_info = obj.customer_name;
                    if (obj.person_name && obj.person_name !== obj.customer_name) {
                        customer_info += '<br><small class="text-muted">' + obj.person_name;
                        if (obj.relative_type) {
                            customer_info += ' (' + obj.relative_type + ')';
                        }
                        customer_info += '</small>';
                    }
                    return customer_info;
                }},
                {"name" : "service_name", "data" : function(obj){ 
                    var service_info = obj.service_name;
                    if (obj.service_category) {
                        service_info += '<br><small class="text-muted">' + obj.service_category + '</small>';
                    }
                    if (obj.servicio_duracion_minutos) {
                        service_info += '<br><span class="badge bg-info">' + obj.servicio_duracion_minutos + ' min</span>';
                    }
                    return service_info;
                }},
                {"name" : "resource_name", "data" : function(obj){ 
                    var resource_info = obj.resource_name;
                    if (obj.location_name) {
                        resource_info += '<br><small class="text-muted">' + obj.location_name + '</small>';
                    }
                    return resource_info;
                }},
                {"name": "status", "data" : function(obj){ 
                    if (obj.status && obj.status_color) {
                        return '<span class="badge" style="background-color: ' + obj.status_color + '">' + obj.status + '</span>';
                    }
                    return obj.status || '-';
                }},
                {"data" : function(obj){
                    var str_btns = "<div class='text-center'>";
                    var view_url = "/admin/appointments/" + obj.id + "/edit";
                    //
                    str_btns += " <a href='" + view_url + "' class='btn btn-sm btn-flat btn-info' title='Editar'><i class='fas fa-pencil-alt'></i></a> ";
                    //str_btns += " <button type='button' class='btn btn-sm btn-flat btn-danger btn-eliminar' data-id='" + obj.id + "' title='Eliminar'><i class='fas fa-trash'></i></button>";
                    
                    str_btns += "</div>";
                    return str_btns;
                }},
            ],
            columnDefs: [
                { "targets": [0, 8],"orderable": false },
                { "targets": "_all", "searchable": false }
            ],
            deferLoading: true,
            hdrBtnsSearch: true,
            order: [[ 2, "desc" ], [ 3, "desc" ]] // Order by date and time
        },
        /*
        * ADD MODE
        * */
        onAddReady: function(data){
            // Focus on first field
            $("#customer_id").focus();
        },
        onGridReady: function(opts){
            // Additional grid functionality if needed
        },
        onSectionReady: function(opts){
            // Filter handlers
            $("#filter_status").change(function(){
                filterGrid();
            });

            $("#filter_date_from").change(function(){
                filterGrid();
            });

            $("#filter_date_to").change(function(){
                filterGrid();
            });


            app.loadResources = function(){
                //
                let location_id = $("#filter_location_id").val();
                $("#filter_resource_id").html("");
                //
                if (location_id){
                    // Load resources filter
                    loadSelectAjax({
                        id: "#filter_resource_id",
                        url: app.admin_url + "/appointments/resources/" + location_id + "/list-available",
                        parseFields: function(item){
                            return item.name;
                        },
                        prependEmptyOption: true,
                        emptyOptionText: "--todos",
                        saveValue: true,
                        enable: true,
                        onChange: function(){
                            filterGrid();
                        },
                        onReady: function(){
                            filterGrid();
                        }
                    });
                } else {
                    //
                    $("<option>")
                        .text("--todos")    
                        .val("")
                        .appendTo("#filter_resource_id");
                    //
                    filterGrid();
                }
            }

            // Load locations filter
            loadSelectAjax({
                id: "#filter_location_id",
                url: app.admin_url + "/locations/list",
                parseFields: function(item){
                    return item.name;
                },
                prependEmptyOption: true,
                emptyOptionText: "--todas",
                saveValue: true,
                enable: true,
                onChange: function(){
                    app.loadResources();
                },
                onReady: function(){
                    app.loadResources();
                }
            });



            // Load locations filter
            loadSelectAjax({
                id: "#filter_status_id",
                url: app.admin_url + "/sys/appointments-status",
                parseFields: function(item){
                    return item.status;
                },
                prependEmptyOption: true,
                emptyOptionText: "--todos",
                saveValue: true,
                enable: true,
                onChange: function(){
                    filterGrid();
                },
                onReady: function(){
                    /**/
                }
            });

            
        }
    });

    //
    function filterGrid(){
        //
        var filter_location_id = $("#filter_location_id").val();
        var filter_resource_id = $("#filter_resource_id").val();
        var filter_status_id = $("#filter_status_id").val();
        var filter_date_from = $("#filter_date_from").val();
        var filter_date_to = $("#filter_date_to").val();
        
        var url = app.admin_url + "/appointments";
        var params = [];
        
        if (filter_location_id) {
            params.push("lid=" + filter_location_id);
        }
        if (filter_resource_id) {
            params.push("rid=" + filter_resource_id);
        }
        if (filter_status_id) {
            params.push("sid=" + filter_status_id);
        }
        if (filter_date_from && filter_date_to) {
            params.push("date_from=" + filter_date_from);
            params.push("date_to=" + filter_date_to);
        }
        
        if (params.length > 0) {
            url += "?" + params.join("&");
        }
        
        $("#grid_section").DataTable().ajax.url(url);
        $("#grid_section").DataTable().ajax.reload();
    }

})(jQuery);