$('.edit_modal').on('show.bs.modal', function (event) {
  console.log('osama');

  var button = $(event.relatedTarget);

  var data = button.data('id');
  console.log(data);

  var modal = $(this);

  $.each(data, function (key, value) {
    var element = modal.find('.modal-body #ed_' + key);

    // التحقق من وجود العنصر داخل المودال
    if (element.length) {
      // فحص نوع عنصر HTML
      var tagName = element.prop('tagName').toLowerCase();
      var inputType = element.attr('type') ? element.attr('type').toLowerCase() : '';
      // console.log(tagName);
      // console.log(inputType);
      if (tagName === 'input') {
        if (inputType === 'checkbox' || inputType === 'radio') {
          element.prop('checked', value);
        } else {
          element.val(value);
        }
      } else if (tagName === 'textarea') {
        element.val(value);
        element.text(value);
      } else if (tagName === 'select') {
        element.val(value).change();
      } else {
        element.text(value);
      }
    }

    // إذا كان المفتاح يشير إلى عنصر في header مثل العنوان
    if (key === 'name') {
      modal.find('.modal-header #ed_title').text(value);
    }

    if (key === 'content') {
      CKEDITOR.instances['ed_content'].setData(value);
    }

    if (key === 'key') {
      modal.find('.modal-header #ed_title').text(value);
    }

    if (key === 'title') {
      modal.find('.modal-header #title').text(value);
    }

    if (key === 're_title') {
      modal.find('.modal-header #ed_rece_title').text(value);
    }

    if (key === 'discount') {
      modal.find('.modal-body #ed_discount_show').text(value);
    }

    if (key === 'image') {
      modal.find('.modal-body #ed_image').attr('src', value);
    }
  });
});

$('#delete_modal').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var id = button.data('id');
  var name = button.data('name');

  var modal = $(this);
  modal.find('.modal-body #de_id').val(id);
  modal.find('.modal-body #de_title').text(name);
});
