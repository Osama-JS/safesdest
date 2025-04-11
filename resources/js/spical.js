function setupImagePreview(inputSelector, imgSelector) {
  const input = document.querySelector(inputSelector);
  const impBtn = document.querySelector(imgSelector);

  impBtn.addEventListener('click', function () {
    input.click();
  });
  input.addEventListener('change', function () {
    const reader = new FileReader();
    reader.addEventListener('load', () => {
      document.querySelector(imgSelector).src = reader.result;
    });
    reader.readAsDataURL(this.files[0]);
  });
}

// تطبيق الدالة على مدخلات الصور والأيقونات
setupImagePreview('.file-input-image', '.preview-image');
