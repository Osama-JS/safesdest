// Sales Order Management
let currentStep = 1;
let selectedProduct = null;
let orderData = {};
let map, marker;

$(document).ready(function () {
  // Initialize DataTable
  $('#salesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: window.salesRoutes.data,
    columns: [
      { data: 'invoice_number', name: 'invoice_number' },
      { data: 'customer_name', name: 'customer_name' },
      { data: 'final_total', name: 'final_total' },
      { data: 'status', name: 'status' },
      { data: 'creator_name', name: 'creator_name' },
      { data: 'created_at', name: 'created_at' },
      { data: 'action', name: 'action', orderable: false, searchable: false }
    ]
  });

  // Initialize Select2
  $('.select2').select2({
    dropdownParent: $('#createOrderModal')
  });

  // Load products when modal opens
  $('#createOrderModal').on('shown.bs.modal', function () {
    if ($('#productsContainer').children().length === 0) {
      loadProducts();
    }
  });

  // Reset modal on close
  $('#createOrderModal').on('hidden.bs.modal', function () {
    resetModal();
  });

  // Pricing type change
  $('input[name="delivery_pricing_type"]').on('change', function () {
    if ($(this).val() === 'manual') {
      $('#manualPriceContainer').show();
      $('#adPriceContainer').hide();
    } else if ($(this).val() === 'ad') {
      $('#manualPriceContainer').hide();
      $('#adPriceContainer').show();
    } else {
      $('#manualPriceContainer').hide();
      $('#adPriceContainer').hide();
    }
  });

  // Quantity change - calculate price and load vehicles
  $('#quantity, #customerId').on('change', function () {
    if (selectedProduct && $('#quantity').val() > 0) {
      calculatePrice();
      loadMatchingVehicles();
    }
  });

  // Navigation buttons
  $('#btnNext').on('click', function () {
    if (validateStep(currentStep)) {
      goToStep(currentStep + 1);
    }
  });

  $('#btnPrevious').on('click', function () {
    goToStep(currentStep - 1);
  });

  $('#btnSubmit').on('click', function () {
    submitOrder();
  });
});

function loadProducts() {
  $.ajax({
    url: window.salesRoutes.products,
    type: 'GET',
    success: function (response) {
      if (response.status === 1) {
        let html = '';
        response.data.forEach(function (product) {
          let imgSrc = product.image ? baseUrl + product.image : '/assets/img/default-product.png';
          html += `
                        <div class="col-md-4 mb-3">
                            <div class="card product-card" data-product-id="${product.id}" data-product='${JSON.stringify(product)}'>
                                <img src="${imgSrc}" class="card-img-top" alt="${product.name}">
                                <div class="card-body">
                                    <h6 class="card-title">${product.name}</h6>
                                    <p class="card-text mb-1"><small>${window.translations.price}: ${product.price} / ${product.unit}</small></p>
                                    <p class="card-text mb-0"><small>${window.translations.minOrder}: ${product.minimum_order} ${product.unit}</small></p>
                                </div>
                            </div>
                        </div>
                    `;
        });
        $('#productsContainer').html(html);

        // Product card click
        $('.product-card').on('click', function () {
          $('.product-card').removeClass('selected');
          $(this).addClass('selected');
          selectedProduct = $(this).data('product');
          orderData.product_id = selectedProduct.id;
          goToStep(2);
        });
      }
    }
  });
}

function calculatePrice() {
  $.ajax({
    url: window.salesRoutes.calculatePrice,
    type: 'POST',
    data: {
      _token: window.csrfToken,
      product_id: selectedProduct.id,
      quantity: $('#quantity').val(),
      customer_id: $('#customerId').val()
    },
    success: function (response) {
      if (response.status === 1) {
        $('#totalPrice').text(response.data.formatted_total);
        orderData.unit_price = response.data.unit_price;
        orderData.total = response.data.total;
      }
    }
  });
}

function loadMatchingVehicles() {
  console.log('Loading matching vehicles for product:', selectedProduct.id, 'quantity:', $('#quantity').val());

  $.ajax({
    url: window.salesRoutes.matchingVehicles,
    type: 'POST',
    data: {
      _token: window.csrfToken,
      product_id: selectedProduct.id,
      quantity: $('#quantity').val()
    },
    success: function (response) {
      console.log('Vehicles response:', response);
      if (response.status === 1) {
        let options = `<option value="">${window.translations.selectVehicle}</option>`;
        if (response.data && response.data.length > 0) {
          response.data.forEach(function (vehicle) {
            options += `<option value="${vehicle.id}">${vehicle.name} (${window.translations.max}: ${vehicle.max_capacity})</option>`;
          });
        } else {
          options += `<option value="" disabled>No vehicles available for this quantity</option>`;
        }
        $('#vehicleSizeId').html(options);
      } else {
        console.error('Error loading vehicles:', response.errors);
        alert('Error loading vehicles: ' + JSON.stringify(response.errors));
      }
    },
    error: function (xhr, status, error) {
      console.error('AJAX error loading vehicles:', xhr.responseText);
      alert('Error loading vehicles. Check console for details.');
    }
  });
}

function initializeMap() {
  if (!map) {
    mapboxgl.accessToken = window.mapboxToken;
    map = new mapboxgl.Map({
      container: 'deliveryMap',
      style: 'mapbox://styles/mapbox/streets-v11',
      center: [46.6753, 24.7136],
      zoom: 10
    });

    marker = new mapboxgl.Marker({
      draggable: true
    })
      .setLngLat([46.6753, 24.7136])
      .addTo(map);

    marker.on('dragend', updateDeliveryLocation);
    map.on('click', function (e) {
      marker.setLngLat(e.lngLat);
      updateDeliveryLocation();
    });

    map.addControl(
      new MapboxGeocoder({
        accessToken: mapboxgl.accessToken,
        mapboxgl: mapboxgl,
        marker: false
      })
    );
  }
}

function updateDeliveryLocation() {
  const lngLat = marker.getLngLat();
  $('#deliveryLat').val(lngLat.lat);
  $('#deliveryLng').val(lngLat.lng);
  $('#deliveryAddress').val(`${lngLat.lat}, ${lngLat.lng}`);
}

function validateStep(step) {
  if (step === 1) {
    if (!selectedProduct) {
      alert(window.translations.pleaseSelectProduct);
      return false;
    }
  } else if (step === 2) {
    if (!$('#customerId').val()) {
      alert(window.translations.pleaseSelectCustomer);
      return false;
    }
    if (!$('#quantity').val() || $('#quantity').val() <= 0) {
      alert(window.translations.pleaseEnterQuantity);
      return false;
    }
    if (!$('#vehicleSizeId').val()) {
      alert(window.translations.pleaseSelectVehicle);
      return false;
    }
    if (!$('#templateId').val()) {
      alert(window.translations.pleaseSelectTemplate);
      return false;
    }
  } else if (step === 3) {
    const pricingType = $('input[name="delivery_pricing_type"]:checked').val();
    if (pricingType === 'manual' && !$('#manualDeliveryPrice').val()) {
      alert(window.translations.pleaseEnterDeliveryPrice);
      return false;
    }
    if (pricingType === 'ad') {
      if (!$('#adMinPrice').val() || !$('#adMaxPrice').val()) {
        alert(window.translations.pleaseEnterMinMaxPrice);
        return false;
      }
    }
    if (!$('#deliveryLat').val() || !$('#deliveryLng').val()) {
      alert(window.translations.pleaseSelectDeliveryLocation);
      return false;
    }
  }
  return true;
}

function goToStep(step) {
  // Hide all tabs
  $('.tab-pane').removeClass('show active');

  // Show target tab
  $(`#step${step}`).addClass('show active');

  // Update step indicator
  $('.step').removeClass('active');
  for (let i = 1; i < step; i++) {
    $(`.step[data-step="${i}"]`).addClass('completed');
  }
  $(`.step[data-step="${step}"]`).addClass('active');

  // Update buttons
  if (step === 1) {
    $('#btnPrevious').hide();
    $('#btnNext').show();
    $('#btnSubmit').hide();
  } else if (step === 4) {
    $('#btnPrevious').show();
    $('#btnNext').hide();
    $('#btnSubmit').show();
    updateSummary();
  } else {
    $('#btnPrevious').show();
    $('#btnNext').show();
    $('#btnSubmit').hide();
  }

  // Special handling for step 2
  if (step === 2 && selectedProduct) {
    $('#productInfo').show();
    $('#infoProductName').text(selectedProduct.name);
    $('#infoProductUnit').text(selectedProduct.unit);
    $('#infoProductMinOrder').text(selectedProduct.minimum_order + ' ' + selectedProduct.unit);
  }

  // Initialize map on step 3
  if (step === 3) {
    setTimeout(initializeMap, 100);
  }

  currentStep = step;
}

function updateSummary() {
  $('#summaryProduct').text(selectedProduct.name);
  $('#summaryQuantity').text($('#quantity').val() + ' ' + selectedProduct.unit);
  $('#summaryUnitPrice').text(orderData.unit_price);
  $('#summaryTotal').text(orderData.total);

  $('#summaryVehicle').text($('#vehicleSizeId option:selected').text());

  const pricingType = $('input[name="delivery_pricing_type"]:checked').val();
  $('#summaryPricingType').text(pricingType.charAt(0).toUpperCase() + pricingType.slice(1));

  $('#summaryPickup').text(selectedProduct.address || 'Product Location');
  $('#summaryDelivery').text($('#deliveryAddress').val());

  let deliveryFee = 0;
  if (pricingType === 'manual') {
    deliveryFee = parseFloat($('#manualDeliveryPrice').val() || 0);
  }
  $('#summaryDeliveryFee').text(deliveryFee.toFixed(2));

  const grandTotal = parseFloat(orderData.total) + deliveryFee;
  $('#summaryGrandTotal').text(grandTotal.toFixed(2));
}

function submitOrder() {
  const formData = {
    _token: window.csrfToken,
    customer_id: $('#customerId').val(),
    product_id: selectedProduct.id,
    quantity: $('#quantity').val(),
    vehicle_size_id: $('#vehicleSizeId').val(),
    template_id: $('#templateId').val(),
    delivery_pricing_type: $('input[name="delivery_pricing_type"]:checked').val(),
    delivery_lat: $('#deliveryLat').val(),
    delivery_lng: $('#deliveryLng').val(),
    delivery_address: $('#deliveryAddress').val(),
    manual_delivery_price: $('#manualDeliveryPrice').val(),
    ad_min_price: $('#adMinPrice').val(),
    ad_max_price: $('#adMaxPrice').val(),
    ad_notes: $('#adNotes').val(),
    conditions: $('#conditions').val()
  };

  $.ajax({
    url: window.salesRoutes.store,
    type: 'POST',
    data: formData,
    success: function (response) {
      if (response.status === 1) {
        alert(response.message);
        $('#createOrderModal').modal('hide');
        $('#salesTable').DataTable().ajax.reload();
        window.location.href = window.salesRoutes.baseUrl + '/' + response.invoice_id;
      } else {
        alert('Error: ' + JSON.stringify(response.errors));
      }
    },
    error: function (xhr) {
      alert('Error: ' + xhr.responseJSON.error);
    }
  });
}

function resetModal() {
  currentStep = 1;
  selectedProduct = null;
  orderData = {};
  $('.tab-pane').removeClass('show active');
  $('#step1').addClass('show active');
  $('.step').removeClass('active completed');
  $('.step[data-step="1"]').addClass('active');
  $('#btnPrevious').hide();
  $('#btnNext').show();
  $('#btnSubmit').hide();
  $('.product-card').removeClass('selected');
  $('input, select, textarea').val('');
  $('#productInfo').hide();
  if (map) {
    map.remove();
    map = null;
    marker = null;
  }
}
