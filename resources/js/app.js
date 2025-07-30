/**
 * ===================================================================
 * MAIN APPLICATION ENTRY POINT
 * ===================================================================
 * This file serves as the main entry point for the application's
 * JavaScript functionality and security configurations.
 */

// استيراد ملف Bootstrap للتهيئة الأساسية
import './bootstrap';

// ===================================================================
// ASSET IMPORTS
// ===================================================================

/**
 * استيراد الأصول الثابتة (الصور والخطوط)
 * يتم تحميل هذه الملفات تلقائياً عند البناء
 */
import.meta.glob([
  '../assets/img/**', // جميع الصور
  // '../assets/json/**',    // ملفات JSON (معطلة حالياً)
  '../assets/vendor/fonts/**' // خطوط المكتبات الخارجية
]);

// ===================================================================
// SECURITY CONFIGURATIONS (PRODUCTION ONLY)
// ===================================================================

/**
 * إعدادات الأمان للبيئة الإنتاجية
 * تمنع فتح أدوات المطور والوصول لكود المصدر
 */
if (import.meta.env.PROD) {
  /**
   * منع استخدام اختصارات لوحة المفاتيح لأدوات المطور
   * F12, Ctrl+Shift+I/J/C, Ctrl+U
   */
  document.addEventListener('keydown', function (e) {
    if (
      e.key === 'F12' || // أدوات المطور
      (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key)) || // فحص العناصر
      (e.ctrlKey && e.key === 'U') // عرض المصدر
    ) {
      e.preventDefault();
    }
  });

  /**
   * منع القائمة السياقية (النقر بالزر الأيمن)
   */
  document.addEventListener('contextmenu', e => e.preventDefault());
}
