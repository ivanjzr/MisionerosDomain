define(function(){
	function moduleReady(modal, data){
		//console.log(modal, data);




		//
		const map = new google.maps.Map(document.getElementById("map"), {
			disableDefaultUI: true,
			center: { lat: 31.761877, lng: -106.485023 },
			zoom: 13,
		});


		//
		const pacInput  = document.getElementById("pac-input");

		//
		const options = {
			fields: ["address_components", "formatted_address", "geometry", "name"],
			strictBounds: false,
			types: ["address"],
			componentRestrictions: { country: ["us"] },
		};
		//map.controls[google.maps.ControlPosition.TOP_CENTER].push(card);





		const autocomplete = new google.maps.places.Autocomplete(pacInput, options);


		// Bind the map's bounds (viewport) property to the autocomplete object,
		// so that the autocomplete requests use the current map bounds for the
		// bounds option in the request.
		autocomplete.bindTo("bounds", map);




		//
		var arr_markers = [];




		//
		function addMarker(lat, lng, str_content, open_marker){
			//
			var info_win = new google.maps.InfoWindow();
			info_win.setContent(str_content);
			//
			var the_marker = new google.maps.Marker({
				map,
				anchorPoint: new google.maps.Point(0, -29),
			});
			//
			the_marker.setPosition({
				lat: lat,
				lng: lng
			});
			the_marker.setVisible(true);
			//
			if (open_marker){
				info_win.open(map, the_marker);
			}
			//
			google.maps.event.addListener(the_marker, 'click', function () {
				info_win.setContent(str_content);
				info_win.open(map, the_marker);
			});


			//
			arr_markers.push(the_marker);
			//
			return the_marker;
		}




		//
		var place_address = null;
		var place_lat = null;
		var place_lng = null;
		var place_city_code = null;
		var place_state_code = null;



		//
		function setSelectedPlace(place){


			//
			$.each(place.address_components, function(idx, item){
				//console.log(item);
				//
				$.each(item.types, function(idx2, types){
					//console.log(idx2, types);
					if ( types === "locality"){
						place_city_code = item.short_name;
					}
					//
					if ( types === "administrative_area_level_1" ){
						place_state_code = item.short_name;
					}
				});
			});

			//
			if (!place_city_code){
				$.each(place.address_components, function(idx, item){
					$.each(item.types, function(idx2, types){
						//console.log(idx2, types);
						if ( types === "administrative_area_level_2" ){
							place_city_code = item.short_name;
						}
					});
				});
			}

			//
			place_address = helperClearText(place.formatted_address, "EE. UU.");
			place_lat = place.geometry.location.lat();
			place_lng = place.geometry.location.lng();




			//
			$(".selected_address").html("&raquo; " + place_address);
			$(".selected_address, #btnSelectAddress").show();



			//
			var customer_address_marker = addMarker(place.geometry.location.lat(), place.geometry.location.lng(), "<div style='text-align: center'> <span style='color:darkgreen;font-weight: bold;'> Resultados </span><br />" + place_address + "</div>", true);
			customer_address_marker.setIcon('http://maps.google.com/mapfiles/ms/icons/yellow-dot.png')
			//
			return customer_address_marker;
		}



		//
		function helperClearText(str_text, str_concidence){
			return str_text.replace(str_concidence,'');
		}


		//
		function resetMarkers(){
			for(i=0; i<arr_markers.length; i++){
				arr_markers[i].setMap(null);
			}
		}







		/*
		*
		* PLACE CHANGED EVT
		*
		* */
		autocomplete.addListener("place_changed", () => {


			// Reset all
			$("#sel_address_msgs").html("");
			resetMarkers();


			//
			const place = autocomplete.getPlace();
			//console.log(place);

			//
			if (!place.geometry || !place.geometry.location) {
				// User entered the name of a Place that was not suggested and
				// pressed the Enter key, or the Place Details request failed.
				window.alert("No details available for input: '" + place.name + "'");
				return;
			}


			//
			var bounds = new google.maps.LatLngBounds();


			//
			var customer_address_marker = setSelectedPlace(place);
			bounds.extend(customer_address_marker.position);
			//console.log(bounds);


			//var store_marker = addMarker(place_lat, place_lng, "<div style='text-align: center'> this is the info </div>", true);
			//store_marker.setIcon('http://maps.google.com/mapfiles/ms/icons/green-dot.png');
			//bounds.extend(store_marker.position);



			//
			//map.fitBounds(bounds);
			//map.setZoom(17);
			map.setCenter(place.geometry.location);
			/*
            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            }
            */


			//console.log(arr_markers);


			//
			var str_info = "<div style='font-size: 14px;line-height: 18px;margin: 10px 0 0'>";
			str_info += "<div style='color:green;'> " + place_address + " - " + place_state_code + " - " + place_city_code + " </div>";

			//
			str_info += "</div>";
			$("#sel_address_msgs").append(str_info);


		});













		//
		$("#btnSelectAddress").click(function(e){
			e.preventDefault();


			//
			var place_location = {
				address: place_address,
				lat: place_lat,
				lng: place_lng,
				city_code: place_city_code,
				state_code: place_state_code
			}
			//localStorage.setItem("user_location", JSON.stringify(user_location));

			//
			$("#sel_address_msgs").html("");
			$("#modal-address").find('.modal').modal("hide");

			//
			$(document).trigger(data.bindFuncName, place_location);

		});




		//
		$("#pac-input").focus();

	}
	return {init: moduleReady}
});






