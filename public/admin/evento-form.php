<?php
require_once __DIR__ . '/../../src/bootstrap.php';

Auth::requireAdmin();

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$event = null;
if ($eventId !== null) {
    $event = EventModel::find($eventId);
    if ($event === null || !EventModel::belongsToOrganizer($eventId, (int) Auth::id())) {
        http_response_code(404);
        die('Evento não encontrado.');
    }
}

$isNew = $event === null;
$states = LocationService::states();
$categories = event_categories();
$statuses = event_statuses($isNew);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $venueName = trim($_POST['venue_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $startsAt = trim($_POST['starts_at'] ?? '');
    $endsAt = trim($_POST['ends_at'] ?? '');

    // Categoria: escolhida na lista fixa ou digitada quando for "Outro".
    $category = trim($_POST['category'] ?? '');
    if ($category === '__outro__') {
        $category = trim($_POST['category_other'] ?? '');
        if ($category === '') {
            $errors[] = 'Descreva a categoria do evento.';
        }
    } elseif (!in_array($category, $categories, true)) {
        $errors[] = 'Selecione uma categoria válida.';
        $category = '';
    }

    // Cidade é validada contra a lista real de municípios da UF escolhida.
    $state = strtoupper(trim($_POST['state'] ?? ''));
    $city = trim($_POST['city'] ?? '');

    if (!LocationService::isValidState($state)) {
        $errors[] = 'Selecione o estado do evento.';
        $state = '';
        $city = '';
    } elseif ($city === '') {
        $errors[] = 'Selecione a cidade do evento.';
    } else {
        $validCities = LocationService::cities($state);
        if ($validCities === []) {
            $errors[] = 'Não foi possível carregar a lista de municípios agora. Tente novamente em instantes.';
        } elseif (!in_array($city, $validCities, true) && $city !== ($event['city'] ?? null)) {
            // A exceção mantém cidades já gravadas antes de a lista oficial existir.
            $errors[] = 'A cidade escolhida não pertence ao estado selecionado.';
            $city = '';
        }
    }

    // Capa: arquivo do computador ou URL, conforme a opção marcada. Aqui só
    // validamos — o arquivo em si é gravado mais abaixo, depois que todo o
    // resto passar, para não deixar imagem órfã no servidor quando o
    // formulário volta com erro.
    $coverMode = ($_POST['cover_mode'] ?? 'url') === 'upload' ? 'upload' : 'url';
    $coverImage = $event['cover_image'] ?? null;
    $previousCover = $coverImage;

    if ($coverMode === 'url') {
        $typedUrl = trim($_POST['cover_image'] ?? '');
        if ($typedUrl !== '' && !filter_var($typedUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'A URL da imagem de capa é inválida.';
        } else {
            $coverImage = $typedUrl !== '' ? $typedUrl : null;
        }
    } elseif ($isNew && ($_FILES['cover_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Escolha o arquivo de imagem da capa.';
    }

    // Cancelado e finalizado só existem na edição — ver event_statuses().
    $status = in_array($_POST['status'] ?? '', array_keys($statuses), true)
        ? $_POST['status']
        : 'draft';

    if ($title === '' || mb_strlen($title) < 3) {
        $errors[] = 'Informe um título com ao menos 3 caracteres.';
    }
    if ($startsAt === '' || strtotime($startsAt) === false) {
        $errors[] = 'Informe uma data/hora de início válida.';
    } elseif (strtotime($startsAt) < time() && ($isNew || date('Y-m-d H:i:s', strtotime($startsAt)) !== $event['starts_at'])) {
        // A vitrine pública só lista eventos futuros (ver EventModel::allPublished),
        // então uma data no passado publicaria um evento invisível. A exceção do
        // else permite editar um evento antigo sem ser obrigado a mudar a data.
        $errors[] = 'A data de início precisa ser no futuro: eventos que já começaram somem da listagem pública.';
    }

    // Grava o arquivo enviado apenas quando nada mais falhou.
    if (empty($errors) && $coverMode === 'upload') {
        $uploadError = null;
        $stored = store_cover_upload($_FILES['cover_file'] ?? [], $uploadError);
        if ($uploadError !== null) {
            $errors[] = $uploadError;
        } elseif ($stored !== null) {
            $coverImage = $stored;
        }
    }

    if (empty($errors)) {
        $data = [
            'title' => $title,
            'description' => $description,
            'category' => $category !== '' ? $category : 'Outro',
            'venue_name' => $venueName,
            'address' => $address,
            'state' => $state !== '' ? $state : null,
            'city' => $city,
            'starts_at' => date('Y-m-d H:i:s', strtotime($startsAt)),
            'ends_at' => $endsAt !== '' && strtotime($endsAt) !== false ? date('Y-m-d H:i:s', strtotime($endsAt)) : null,
            'cover_image' => $coverImage,
            'status' => $status,
        ];

        if (!$isNew) {
            EventModel::update((int) $event['id'], $data);
            if ($previousCover !== $coverImage) {
                delete_cover_upload($previousCover);
            }
            flash_set('success', 'Evento atualizado com sucesso.');
            redirect('admin/index.php');
        }

        $data['organizer_id'] = (int) Auth::id();
        $data['slug'] = EventModel::uniqueSlug($title);
        $newId = EventModel::create($data);
        flash_set('success', 'Evento criado com sucesso. Agora cadastre os tipos de ingresso.');
        redirect('admin/ingressos-form.php?event_id=' . $newId);
    }

    flash_set('error', implode(' ', $errors));
}

$pageTitle = $isNew ? 'Novo evento' : 'Editar evento';
include __DIR__ . '/../../templates/header.php';

function fval(?array $event, string $key, string $post = ''): string
{
    if (isset($_POST[$key])) {
        return e((string) $_POST[$key]);
    }
    return e($event[$key] ?? $post);
}

// Valores atuais dos combos, considerando o que foi postado numa tentativa com erro.
$currentCategory = $_POST['category'] ?? ($event['category'] ?? '');
$categoryIsOther = $currentCategory !== '' && !in_array($currentCategory, $categories, true);
$categoryOther = $_POST['category_other'] ?? ($categoryIsOther ? $currentCategory : '');

$currentState = strtoupper($_POST['state'] ?? ($event['state'] ?? ''));
$currentCity = $_POST['city'] ?? ($event['city'] ?? '');

// A lista já sai renderizada quando há estado, para a página funcionar mesmo
// antes do JS rodar (e para o valor atual aparecer selecionado na edição).
$cityOptions = LocationService::isValidState($currentState) ? LocationService::cities($currentState) : [];
if ($currentCity !== '' && $cityOptions !== [] && !in_array($currentCity, $cityOptions, true)) {
    array_unshift($cityOptions, $currentCity);
}

$currentStatus = $_POST['status'] ?? ($event['status'] ?? 'draft');
$currentCover = $event['cover_image'] ?? '';
$coverIsUpload = $currentCover !== '' && !preg_match('#^https?://#i', $currentCover);
$currentMode = $_POST['cover_mode'] ?? ($coverIsUpload ? 'upload' : 'url');
?>

<section class="admin-form">
    <div class="page-head">
        <h1><?= e($pageTitle) ?></h1>
        <p><?= $isNew ? 'Preencha os dados do evento' : 'Editando “' . e($event['title']) . '”' ?></p>
    </div>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-grid">
            <label class="form-full">Título
                <input type="text" name="title" required value="<?= fval($event, 'title') ?>">
            </label>

            <label class="form-full">Descrição
                <textarea name="description" rows="5"><?= fval($event, 'description') ?></textarea>
            </label>

            <label>Categoria
                <select name="category" id="categorySelect" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $currentCategory === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                    <option value="__outro__" <?= $categoryIsOther ? 'selected' : '' ?>>Outro...</option>
                </select>
            </label>

            <label id="categoryOtherField" <?= $categoryIsOther ? '' : 'hidden' ?>>Qual categoria?
                <input type="text" name="category_other" id="categoryOtherInput" value="<?= e($categoryOther) ?>" placeholder="Digite a categoria">
            </label>

            <label>Estado
                <select name="state" id="stateSelect" required
                        data-cities-url="<?= e(base_url('api/cidades.php')) ?>">
                    <option value="">Selecione o estado...</option>
                    <?php foreach ($states as $uf => $name): ?>
                        <option value="<?= e($uf) ?>" <?= $currentState === $uf ? 'selected' : '' ?>>
                            <?= e($name) ?> (<?= e($uf) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Cidade
                <select name="city" id="citySelect" required <?= $cityOptions === [] ? 'disabled' : '' ?>>
                    <?php if ($cityOptions === []): ?>
                        <option value="">Escolha o estado primeiro</option>
                    <?php else: ?>
                        <option value="">Selecione a cidade...</option>
                        <?php foreach ($cityOptions as $c): ?>
                            <option value="<?= e($c) ?>" <?= $currentCity === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <span class="field-hint" id="cityHint"><?= $cityOptions !== [] ? count($cityOptions) . ' municípios' : 'Lista oficial do IBGE' ?></span>
            </label>

            <label>Local (nome do espaço)
                <input type="text" name="venue_name" value="<?= fval($event, 'venue_name') ?>">
            </label>

            <label>Endereço
                <input type="text" name="address" value="<?= fval($event, 'address') ?>">
            </label>

            <label>Início
                <?php // O min só entra em evento novo: num evento antigo ele travaria
                      // o envio do formulário mesmo sem mexer na data. ?>
                <input type="datetime-local" name="starts_at" required
                       <?= $isNew ? 'min="' . e(date('Y-m-d\TH:i')) . '"' : '' ?>
                       value="<?= isset($_POST['starts_at']) ? e($_POST['starts_at']) : ($event ? date('Y-m-d\TH:i', strtotime($event['starts_at'])) : '') ?>">
                <small class="field-hint">O evento só aparece na listagem pública enquanto a data de início não chega.</small>
            </label>

            <label>Término
                <input type="datetime-local" name="ends_at"
                       value="<?= isset($_POST['ends_at']) ? e($_POST['ends_at']) : ($event && $event['ends_at'] ? date('Y-m-d\TH:i', strtotime($event['ends_at'])) : '') ?>">
            </label>

            <div class="form-full field-block">
                <span class="field-legend">Imagem de capa</span>

                <div class="choice-row">
                    <label class="choice">
                        <input type="radio" name="cover_mode" value="url" <?= $currentMode !== 'upload' ? 'checked' : '' ?>>
                        Usar uma URL
                    </label>
                    <label class="choice">
                        <input type="radio" name="cover_mode" value="upload" <?= $currentMode === 'upload' ? 'checked' : '' ?>>
                        Enviar do dispositivo
                    </label>
                </div>

                <div id="coverUrlField" <?= $currentMode === 'upload' ? 'hidden' : '' ?>>
                    <label>Endereço da imagem
                        <input type="url" name="cover_image" placeholder="https://..."
                               value="<?= $coverIsUpload ? '' : fval($event, 'cover_image') ?>">
                    </label>
                </div>

                <div id="coverUploadField" <?= $currentMode === 'upload' ? '' : 'hidden' ?>>
                    <label>Arquivo de imagem
                        <input type="file" name="cover_file" accept="image/jpeg,image/png,image/webp,image/gif">
                    </label>
                    <p class="field-hint">JPG, PNG, WEBP ou GIF, até 4 MB.<?= $coverIsUpload ? ' Deixe vazio para manter a imagem atual.' : '' ?></p>
                </div>

                <?php if ($currentCover !== ''): ?>
                    <div class="cover-preview">
                        <img src="<?= e(cover_url($currentCover)) ?>" alt="Capa atual do evento">
                        <span>Capa atual</span>
                    </div>
                <?php endif; ?>
            </div>

            <label>Status
                <select name="status">
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $currentStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isNew): ?>
                    <span class="field-hint">Cancelado e finalizado ficam disponíveis depois, na edição.</span>
                <?php endif; ?>
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-large"><?= $isNew ? 'Criar evento' : 'Salvar alterações' ?></button>
    </form>
</section>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
