(function () {
  'use strict';

  var root = document.querySelector('.dc26-member-profile[data-rest-url]');
  if (!root) return;

  var restUrl = root.dataset.restUrl;
  var nonce   = root.dataset.nonce;

  var sections    = root.querySelectorAll('[data-section]');
  var tsInstances = {};

  function updateSpecCounter(ts, max, counterEl, noticeEl) {
    var count = ts.items.length;
    if (counterEl) {
      counterEl.textContent = count + '/' + max;
      counterEl.classList.toggle('is-full', count >= max);
    }
    if (noticeEl) {
      noticeEl.classList.toggle('is-visible', count >= max);
    }
  }

  function initTomSelect() {
    if (typeof TomSelect === 'undefined') return;

    var specSelect = root.querySelector('#mp-specialities');
    var langSelect = root.querySelector('#mp-languages');

    if (specSelect && !tsInstances.specialities) {
      var specMax = 7;
      var specCounter = document.getElementById('mp-specialities-counter');
      var specNotice  = document.getElementById('mp-specialities-notice');

      tsInstances.specialities = new TomSelect(specSelect, {
        plugins: ['remove_button', 'optgroup_columns'],
        maxItems: specMax,
        searchField: ['text'],
        placeholder: specSelect.getAttribute('placeholder') || '',
        render: {
          item: function (data, escape) {
            return '<div class="dc26-ts-chip">' + escape(data.text) + '</div>';
          },
          option: function (data, escape) {
            return '<div class="dc26-ts-option">' + escape(data.text) + '</div>';
          },
        },
        onChange: function () {
          updateSpecCounter(this, specMax, specCounter, specNotice);
        },
      });

      updateSpecCounter(tsInstances.specialities, specMax, specCounter, specNotice);
    }

    if (langSelect && !tsInstances.languages) {
      tsInstances.languages = new TomSelect(langSelect, {
        plugins: ['remove_button'],
        maxItems: null,
        searchField: ['text'],
        placeholder: langSelect.getAttribute('placeholder') || '',
        render: {
          item: function (data, escape) {
            return '<div class="dc26-ts-chip">' + escape(data.text) + '</div>';
          },
          option: function (data, escape) {
            return '<div class="dc26-ts-option">' + escape(data.text) + '</div>';
          },
        },
      });
    }
  }

  initTomSelect();

  // Password visibility toggles
  root.querySelectorAll('.dc26-member-profile__pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = btn.closest('.dc26-member-profile__pw-input-wrap').querySelector('input');
      var isVisible = input.type === 'text';
      input.type = isVisible ? 'password' : 'text';
      btn.classList.toggle('is-visible', !isVisible);
      btn.setAttribute('aria-label', isVisible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
    });
  });

  // Password suggestion
  var suggestBtn = document.getElementById('mp-pw-suggest');
  var newPwInput = document.getElementById('mp-new-pw');
  if (suggestBtn && newPwInput) {
    suggestBtn.addEventListener('click', function () {
      var chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*';
      var pw = '';
      var arr = new Uint8Array(16);
      crypto.getRandomValues(arr);
      for (var i = 0; i < arr.length; i++) {
        pw += chars[arr[i] % chars.length];
      }
      newPwInput.type = 'text';
      newPwInput.value = pw;
      newPwInput.select();
      var toggleBtn = newPwInput.closest('.dc26-member-profile__pw-input-wrap').querySelector('.dc26-member-profile__pw-toggle');
      if (toggleBtn) {
        toggleBtn.classList.add('is-visible');
        toggleBtn.setAttribute('aria-label', 'Masquer le mot de passe');
      }
    });
  }

  sections.forEach(function (section) {
    var sectionName = section.dataset.section;
    var editBtn     = section.querySelector('.dc26-member-profile__edit-btn');
    var cancelBtn   = section.querySelector('.dc26-member-profile__cancel-btn');
    var form        = section.querySelector('.dc26-member-profile__form');
    var feedback    = section.querySelector('.dc26-member-profile__feedback');

    if (editBtn) {
      editBtn.addEventListener('click', function () {
        section.classList.add('is-editing');
        if (tsInstances[sectionName]) {
          tsInstances[sectionName].focus();
        }
      });
    }

    if (cancelBtn) {
      cancelBtn.addEventListener('click', function () {
        section.classList.remove('is-editing');
        clearFeedback(feedback);
      });
    }

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (sectionName === 'photo') {
          handlePhotoUpload(section, form, feedback);
        } else {
          handleSectionUpdate(section, sectionName, form, feedback);
        }
      });
    }
  });

  function handleSectionUpdate(section, sectionName, form, feedback) {
    var saveBtn = form.querySelector('.dc26-member-profile__save-btn');
    var originalText = saveBtn.textContent;
    saveBtn.disabled = true;
    saveBtn.textContent = '...';
    clearFeedback(feedback);

    var body = { section: sectionName };

    if (sectionName === 'specialities' || sectionName === 'languages') {
      var ts = tsInstances[sectionName];
      if (ts) {
        body.term_ids = ts.getValue().map(function (v) { return parseInt(v, 10); });
      } else {
        var selectEl = form.querySelector('select[multiple]');
        body.term_ids = Array.from(selectEl.selectedOptions).map(function (o) {
          return parseInt(o.value, 10);
        });
      }
    } else {
      var inputs = form.querySelectorAll('input[name], select[name], textarea[name]');
      inputs.forEach(function (input) {
        body[input.name] = input.value;
      });
    }

    fetch(restUrl + '/update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      body: JSON.stringify(body),
    })
      .then(function (res) { return res.json().then(function (json) { return { ok: res.ok, data: json }; }); })
      .then(function (result) {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;

        if (!result.ok || !result.data.success) {
          showFeedback(feedback, result.data.message || 'Erreur lors de la sauvegarde.', 'error');
          return;
        }

        if (sectionName === 'password') {
          form.reset();
          showFeedback(feedback, result.data.message || 'Enregistré.', 'success');
          return;
        }

        if (result.data.data) {
          updateDisplayFromData(section, sectionName, result.data.data);
        }

        section.classList.remove('is-editing');
        showTemporarySuccess(section);
      })
      .catch(function () {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
        showFeedback(feedback, 'Erreur réseau.', 'error');
      });
  }

  function handlePhotoUpload(section, form, feedback) {
    var fileInput = form.querySelector('input[type="file"]');
    if (!fileInput || !fileInput.files.length) {
      showFeedback(feedback, 'Veuillez sélectionner un fichier.', 'error');
      return;
    }

    var saveBtn = form.querySelector('.dc26-member-profile__save-btn');
    var originalText = saveBtn.textContent;
    saveBtn.disabled = true;
    saveBtn.textContent = '...';
    clearFeedback(feedback);

    var formData = new FormData();
    formData.append('photo', fileInput.files[0]);

    fetch(restUrl + '/photo', {
      method: 'POST',
      headers: { 'X-WP-Nonce': nonce },
      body: formData,
    })
      .then(function (res) { return res.json().then(function (json) { return { ok: res.ok, data: json }; }); })
      .then(function (result) {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;

        if (!result.ok || !result.data.success) {
          showFeedback(feedback, result.data.message || "Erreur lors de l'upload.", 'error');
          return;
        }

        var display = section.querySelector('.dc26-member-profile__display');
        if (display && result.data.photo_url) {
          var img = display.querySelector('.dc26-member-profile__img');
          var placeholder = display.querySelector('.dc26-member-profile__placeholder');

          if (img) {
            img.src = result.data.photo_url;
          } else if (placeholder) {
            var newImg = document.createElement('img');
            newImg.src = result.data.photo_url;
            newImg.className = 'dc26-member-profile__img';
            newImg.alt = '';
            placeholder.replaceWith(newImg);
          }
        }

        form.reset();
        section.classList.remove('is-editing');
        showTemporarySuccess(section);
      })
      .catch(function () {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
        showFeedback(feedback, 'Erreur réseau.', 'error');
      });
  }

  function updateDisplayFromData(section, sectionName, data) {
    switch (sectionName) {
      case 'personal':
        var nameEl = section.querySelector('.dc26-member-profile__name');
        if (nameEl) nameEl.textContent = data.full_name || '';
        var titreEl = section.querySelector('.dc26-member-profile__titre');
        if (titreEl) titreEl.textContent = data.titre || '';
        break;

      case 'address':
        var addrEl = section.querySelector('.dc26-member-profile__address');
        if (addrEl) {
          var parts = [];
          if (data.complement_adresse) parts.push(data.complement_adresse);
          var street = ((data.rue || '') + ' ' + (data.rue_no || '')).trim();
          if (street) parts.push(street);
          if (data.case_postale) parts.push(data.case_postale);
          var city = ((data.npa || '') + ' ' + (data.ville || '')).trim();
          if (city) parts.push(city);
          addrEl.innerHTML = parts.map(function (p) { return escapeHtml(p); }).join('<br>');
        }
        break;

      case 'contact':
        var contactEl = section.querySelector('.dc26-member-profile__contact');
        if (!contactEl) break;
        var pills = [];
        if (data.phone) {
          pills.push('<a class="dc26-member-profile__pill" href="tel:' + escapeAttr(data.phone) + '">' + escapeHtml(data.phone) + '</a>');
        }
        if (data.email) {
          pills.push('<a class="dc26-member-profile__pill" href="mailto:' + escapeAttr(data.email) + '">' + escapeHtml(data.email) + '</a>');
        }
        if (data.homepage_url) {
          pills.push('<a class="dc26-member-profile__pill" href="' + escapeAttr(data.homepage_url) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(data.homepage_label || data.homepage_url) + '</a>');
        }
        if (data.fax) {
          pills.push('<span class="dc26-member-profile__pill">Fax : ' + escapeHtml(data.fax) + '</span>');
        }
        contactEl.innerHTML = pills.join('');
        break;

      case 'specialities':
      case 'languages':
        window.location.reload();
        break;
    }
  }

  function showFeedback(el, message, type) {
    if (!el) return;
    el.textContent = message;
    el.className = 'dc26-member-profile__feedback dc26-member-profile__feedback--' + type;
  }

  function clearFeedback(el) {
    if (!el) return;
    el.textContent = '';
    el.className = 'dc26-member-profile__feedback';
  }

  function showTemporarySuccess(section) {
    section.classList.add('is-saved');
    setTimeout(function () {
      section.classList.remove('is-saved');
    }, 2000);
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function escapeAttr(str) {
    return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
})();
