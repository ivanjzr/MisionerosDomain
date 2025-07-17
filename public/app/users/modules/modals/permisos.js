define(function(){
    function moduleReady(modal, record_info){
        console.log(record_info);

        //
        $("#modal-title").text("Permisos " + record_info.sucursal);
        $(".btnSavePermisos").html("<i class='fas fa-save'></i> Guardar");

        //
        function determineShowPermisos(tipos_permisos){
            //
            if ( tipos_permisos === 1 ){
                $('#tipos_permisos_all').attr("checked", true);
                $('.permisos_container').hide();
            }
            //
            else if ( tipos_permisos === 2 ){
                $('#tipos_permisos_select').attr("checked", true);
                $('.permisos_container').show();
            }
            //
            else {
                $('#tipos_permisos_none').attr("checked", true);
                $('.permisos_container').hide();
            }
        }

        //
        $("input[name=tipos_permisos]").click(function(){
            //
            var tipos_permisos = $('input[name=tipos_permisos]:checked').val()
            determineShowPermisos(parseInt(tipos_permisos));
        });
        //
        determineShowPermisos(parseInt(record_info.tipos_permisos));

        //
        var tree_permisos = $('#tree_permisos').tree({
            primaryKey: 'id',
            uiLibrary: 'bootstrap5',
            cascadeCheck: false,
            iconsLibrary: 'fontawesome',
            dataSource: {
                url: record_info.endpoint_url + "/" + record_info.id + "/permisos",
                success: function(response){
                    //
                    preload(".permisos-preloader");
                    //
                    if (response.length){
                        //
                        tree_permisos.render(response);

                        // Expandir todo el árbol
                        tree_permisos.expandAll();
                        
                        // Agregar botones "marcar todo" después de renderizar
                        setTimeout(function() {
                            addSelectAllButtons();
                        }, 100);
                    }
                    //
                    else{
                        //
                        var err_msg = (response.error) ? response.error : "no fue posible traer los permisos";
                        app.Toast.fire({ icon: 'error', title: err_msg});
                    }
                },
                error: function(response){
                    //
                    preload(".permisos-preloader");
                    alert('Server error.');
                }
            },
            checkboxes: true,
            initialized: function (e) {
                //
                preload(".permisos-preloader", true);
            }
        });

        // Función para agregar botones "marcar todo"
        function addSelectAllButtons() {
            $('#tree_permisos li').each(function() {
                var $li = $(this);
                var nodeId = $li.attr('data-id');
                
                // Solo agregar el botón si el nodo tiene hijos
                if ($li.children('ul').length > 0) {
                    var $displaySpan = $li.find('span[data-role="display"]').first();
                    
                    // Verificar si ya se agregó el link para evitar duplicados
                    if ($displaySpan.length && !$displaySpan.find('.select-all-link').length) {
                        $displaySpan.append(' <a href="#" class="select-all-link text-primary" data-parent-id="' + nodeId + '" style="font-size: 0.75em; text-decoration: underline;">marcar todo</a>');
                    }
                }
            });
            
            // Event handler para "marcar todo"
            $('.select-all-link').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var parentId = $(this).data('parent-id');
                var $parentLi = $('[data-id="' + parentId + '"]');
                var $childNodes = $parentLi.children('ul').children('li');
                var parentNode = tree_permisos.getNodeById(parentId);
                
                // Marcar el padre
                tree_permisos.check(parentNode);
                
                // Marcar todos los hijos
                $childNodes.each(function() {
                    var childId = $(this).attr('data-id');
                    tree_permisos.check(tree_permisos.getNodeById(childId));
                });
            });
        }

        //
        $('.btnSavePermisos').on('click', function () {

            //
            var tipos_permisos = $('input[name=tipos_permisos]:checked').val()
            var checkedIds = tree_permisos.getCheckedNodes();

            //
            preload(".permisos-preloader", true);
            //
            $.ajax({
                type:'POST',
                url: record_info.endpoint_url + "/" + record_info.id + "/permisos",
                data: $.param({
                    tipos_permisos: tipos_permisos,
                    checkedIds: checkedIds
                }),
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                success:function(response){

                    //
                    preload(".permisos-preloader");

                    //
                    if (response.id){
                        //
                        $("#modal-permisos").find('.modal').modal("hide");
                        //
                        app.Toast.fire({ icon: 'success', title: "Permisos actualizados correctamente" });
                        $('#grid_usuario_sucursales').DataTable().ajax.reload();
                    }
                    //
                    else if (response.error){
                        //
                        app.Toast.fire({ icon: 'error', title: response.error});
                    }
                    //
                    else {
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                },
                error: function(){
                    //
                    preload(".permisos-preloader");
                    //
                    app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                }
            });

        });

    }
    return {init: moduleReady}
});