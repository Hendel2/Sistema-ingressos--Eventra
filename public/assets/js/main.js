document.addEventListener('DOMContentLoaded', function () {
    /* ---------- Alertas somem sozinhos ---------- */
    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            alertEl.style.transition = 'opacity .4s ease, transform .4s ease';
            alertEl.style.opacity = '0';
            alertEl.style.transform = 'translateY(-6px)';
            setTimeout(function () { alertEl.remove(); }, 400);
        }, 6000);
    });

    /* ---------- Menu mobile ---------- */
    var navToggle = document.getElementById('navToggle');
    var navLinks = document.getElementById('navLinks');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            var open = navLinks.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            navToggle.textContent = open ? '✕' : '☰';
        });
    }

    /* ---------- Cadastro de evento: campos dependentes ---------- */

    // Categoria "Outro..." revela o campo de texto livre.
    var catSelect = document.getElementById('categorySelect');
    var catField = document.getElementById('categoryOtherField');
    var catInput = document.getElementById('categoryOtherInput');
    if (catSelect && catField) {
        var syncCategory = function () {
            var isOther = catSelect.value === '__outro__';
            catField.hidden = !isOther;
            // required só enquanto visível, senão o navegador trava a validação
            if (catInput) { catInput.required = isOther; }
        };
        catSelect.addEventListener('change', syncCategory);
        syncCategory();
    }

    // Estado escolhido busca os municípios reais daquela UF na API.
    var stateSelect = document.getElementById('stateSelect');
    var citySelect = document.getElementById('citySelect');
    var cityHint = document.getElementById('cityHint');

    if (stateSelect && citySelect) {
        var citiesUrl = stateSelect.getAttribute('data-cities-url');
        var cache = {};

        var setCityState = function (message, disabled) {
            citySelect.innerHTML = '';
            citySelect.appendChild(new Option(message, ''));
            citySelect.disabled = disabled;
        };

        var fillCities = function (list) {
            citySelect.innerHTML = '';
            citySelect.appendChild(new Option('Selecione a cidade...', ''));
            list.forEach(function (city) {
                citySelect.appendChild(new Option(city, city));
            });
            citySelect.disabled = false;
            if (cityHint) { cityHint.textContent = list.length + ' municípios'; }
        };

        stateSelect.addEventListener('change', function () {
            var uf = stateSelect.value;

            if (!uf) {
                setCityState('Escolha o estado primeiro', true);
                if (cityHint) { cityHint.textContent = 'Lista oficial do IBGE'; }
                return;
            }

            if (cache[uf]) {
                fillCities(cache[uf]);
                return;
            }

            setCityState('Carregando municípios...', true);
            if (cityHint) { cityHint.textContent = 'Buscando...'; }

            fetch(citiesUrl + '?uf=' + encodeURIComponent(uf), { credentials: 'same-origin' })
                .then(function (res) {
                    if (!res.ok) { throw new Error('falha ' + res.status); }
                    return res.json();
                })
                .then(function (data) {
                    var list = (data && data.cities) || [];
                    if (!list.length) { throw new Error('lista vazia'); }
                    cache[uf] = list;
                    fillCities(list);
                })
                .catch(function () {
                    setCityState('Não foi possível carregar as cidades', true);
                    if (cityHint) { cityHint.textContent = 'Erro ao buscar — tente trocar o estado de novo'; }
                });
        });
    }

    // Capa: alterna entre informar URL e enviar arquivo.
    var modeRadios = document.querySelectorAll('input[name="cover_mode"]');
    var coverUrlField = document.getElementById('coverUrlField');
    var coverUploadField = document.getElementById('coverUploadField');
    if (modeRadios.length && coverUrlField && coverUploadField) {
        var syncCoverMode = function () {
            var checked = document.querySelector('input[name="cover_mode"]:checked');
            var isUpload = !!checked && checked.value === 'upload';
            coverUrlField.hidden = isUpload;
            coverUploadField.hidden = !isUpload;
        };
        modeRadios.forEach(function (radio) {
            radio.addEventListener('change', syncCoverMode);
        });
        syncCoverMode();
    }

    /* ---------- Alternância de tema — escuro é o padrão ---------- */
    var toggle = document.getElementById('themeToggle');
    if (!toggle) {
        return;
    }

    function isDark() {
        return document.documentElement.getAttribute('data-theme') !== 'light';
    }

    function syncToggleLabel() {
        var dark = isDark();
        toggle.textContent = dark ? '☀' : '☾';
        toggle.setAttribute('aria-label', dark ? 'Ativar tema claro' : 'Ativar tema escuro');
    }

    toggle.addEventListener('click', function () {
        var next = isDark() ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        syncToggleLabel();
    });

    syncToggleLabel();
});
