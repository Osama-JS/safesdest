window.__ = function (key, replacements = {}) {
  let translation = window.translations[key] || key;

  for (const placeholder in replacements) {
    translation = translation.replace(`:${placeholder}`, replacements[placeholder]);
  }

  return translation;
};
