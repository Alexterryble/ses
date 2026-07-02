<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Subir PDF | Contar 1750 días</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{
      --azul:#0b2f5b;
      --azul2:#174a8b;
      --dorado:#c9a646;
      --gris:#f4f7fb;
      --texto:#243447;
      --borde:#dbe4f0;
      --ok:#1f9d55;
      --danger:#dc3545;
    }

    *{
      box-sizing:border-box;
    }

    body{
      margin:0;
      min-height:100vh;
      font-family:"Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background:
        radial-gradient(circle at top right, rgba(201,166,70,.15), transparent 25%),
        radial-gradient(circle at bottom left, rgba(23,74,139,.12), transparent 30%),
        linear-gradient(135deg, #eef3f9 0%, #f8fbff 100%);
      color:var(--texto);
    }

    .page-wrap{
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:30px 15px;
    }

    .upload-shell{
      width:100%;
      max-width:1100px;
      background:#fff;
      border-radius:28px;
      overflow:hidden;
      box-shadow:0 20px 60px rgba(11,47,91,.16);
      display:grid;
      grid-template-columns: 1.05fr 1fr;
    }

    .left-panel{
      background:linear-gradient(160deg, var(--azul) 0%, var(--azul2) 100%);
      color:#fff;
      padding:42px 36px;
      position:relative;
      overflow:hidden;
    }

    .left-panel::before{
      content:"";
      position:absolute;
      width:220px;
      height:220px;
      border-radius:50%;
      background:rgba(255,255,255,.08);
      top:-70px;
      right:-80px;
    }

    .left-panel::after{
      content:"";
      position:absolute;
      width:180px;
      height:180px;
      border-radius:50%;
      background:rgba(201,166,70,.16);
      bottom:-70px;
      left:-70px;
    }

    .badge-top{
      display:inline-block;
      background:rgba(255,255,255,.13);
      border:1px solid rgba(255,255,255,.18);
      border-radius:999px;
      padding:8px 14px;
      font-size:.85rem;
      font-weight:600;
      margin-bottom:18px;
      position:relative;
      z-index:1;
    }

    .left-panel h1{
      font-size:2rem;
      font-weight:800;
      line-height:1.15;
      margin:0 0 14px;
      position:relative;
      z-index:1;
    }

    .left-panel p{
      color:rgba(255,255,255,.86);
      font-size:1rem;
      line-height:1.7;
      margin-bottom:26px;
      position:relative;
      z-index:1;
    }

    .steps{
      display:grid;
      gap:14px;
      position:relative;
      z-index:1;
    }

    .step-card{
      display:flex;
      gap:14px;
      align-items:flex-start;
      background:rgba(255,255,255,.09);
      border:1px solid rgba(255,255,255,.10);
      border-radius:18px;
      padding:15px 16px;
      backdrop-filter: blur(4px);
    }

    .step-icon{
      min-width:42px;
      width:42px;
      height:42px;
      border-radius:12px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.14);
      font-size:1.15rem;
    }

    .step-card h6{
      margin:0 0 4px;
      font-size:.97rem;
      font-weight:700;
      color:#fff;
    }

    .step-card p{
      margin:0;
      font-size:.88rem;
      line-height:1.45;
      color:rgba(255,255,255,.78);
    }

    .right-panel{
      padding:40px 34px;
      background:#fff;
    }

    .right-panel h3{
      font-size:1.5rem;
      font-weight:800;
      color:var(--azul);
      margin-bottom:8px;
    }

    .right-panel .sub{
      color:#6b7a90;
      margin-bottom:26px;
      font-size:.95rem;
    }

    .drop-zone{
      border:2px dashed #bfd0e6;
      border-radius:24px;
      background:linear-gradient(180deg, #fbfdff 0%, #f4f8fc 100%);
      padding:34px 24px;
      text-align:center;
      cursor:pointer;
      transition:.25s ease;
      position:relative;
    }

    .drop-zone:hover{
      border-color:var(--dorado);
      background:linear-gradient(180deg, #fffefb 0%, #fffaf0 100%);
      transform:translateY(-1px);
    }

    .drop-zone.dragover{
      border-color:var(--azul2);
      background:#eef5ff;
      box-shadow:0 0 0 4px rgba(23,74,139,.08);
    }

    .drop-icon{
      width:82px;
      height:82px;
      margin:0 auto 18px;
      border-radius:22px;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:2rem;
      background:linear-gradient(135deg, rgba(11,47,91,.10), rgba(201,166,70,.14));
      color:var(--azul);
    }

    .drop-zone h5{
      margin:0 0 10px;
      font-weight:800;
      color:var(--azul);
      font-size:1.2rem;
    }

    .drop-zone p{
      margin:0;
      color:#6b7a90;
      font-size:.95rem;
    }

    .drop-zone .browse{
      color:var(--azul2);
      font-weight:700;
      text-decoration:underline;
    }

    .hidden-input{
      display:none;
    }

    .file-preview{
      display:none;
      margin-top:18px;
      padding:14px 16px;
      border-radius:16px;
      background:#f7fafc;
      border:1px solid #e3ebf5;
      align-items:center;
      justify-content:space-between;
      gap:12px;
    }

    .file-preview.show{
      display:flex;
    }

    .file-info{
      display:flex;
      align-items:center;
      gap:12px;
      min-width:0;
    }

    .file-badge{
      width:46px;
      height:46px;
      border-radius:14px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(220,53,69,.10);
      color:#dc3545;
      font-size:1.2rem;
      font-weight:700;
    }

    .file-text{
      min-width:0;
    }

    .file-name{
      font-weight:700;
      color:#203246;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      max-width:320px;
    }

    .file-size{
      font-size:.85rem;
      color:#6b7a90;
    }

    .remove-btn{
      border:none;
      background:#fff;
      border:1px solid #d8e2ee;
      color:#55657a;
      border-radius:12px;
      padding:8px 14px;
      font-weight:600;
      transition:.2s ease;
    }

    .remove-btn:hover{
      background:#f1f5f9;
    }

    .note-box{
      margin-top:20px;
      padding:16px 18px;
      border-radius:18px;
      background:rgba(201,166,70,.10);
      border:1px solid rgba(201,166,70,.24);
      color:#6b5521;
      font-size:.93rem;
      line-height:1.55;
    }

    .actions{
      display:flex;
      flex-wrap:wrap;
      gap:12px;
      margin-top:24px;
    }

    .btn-modern{
      min-width:150px;
      border:none;
      border-radius:14px;
      padding:13px 20px;
      font-weight:700;
      transition:.25s ease;
    }

    .btn-submit{
      background:linear-gradient(135deg, var(--azul), var(--azul2));
      color:#fff;
      box-shadow:0 12px 24px rgba(11,47,91,.18);
    }

    .btn-submit:hover{
      transform:translateY(-1px);
      opacity:.97;
    }

    .btn-reset{
      background:#eef3f8;
      color:#334155;
      border:1px solid #d7e1ec;
    }

    .btn-back{
      background:#fff;
      color:#4a5a70;
      border:1px solid #d7e1ec;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
    }

    .status-msg{
      margin-top:14px;
      font-size:.92rem;
      font-weight:600;
      display:none;
    }

    .status-msg.ok{
      display:block;
      color:var(--ok);
    }

    .status-msg.error{
      display:block;
      color:var(--danger);
    }

    @media (max-width: 991.98px){
      .upload-shell{
        grid-template-columns:1fr;
      }

      .left-panel,
      .right-panel{
        padding:30px 22px;
      }

      .file-name{
        max-width:180px;
      }
    }

    @media (max-width: 575.98px){
      .actions{
        flex-direction:column;
      }

      .btn-modern,
      .btn-back{
        width:100%;
      }

      .file-preview.show{
        flex-direction:column;
        align-items:stretch;
      }

      .remove-btn{
        width:100%;
      }
    }
  </style>
</head>
<body>

  <div class="page-wrap">
    <div class="upload-shell">

      <!-- Panel izquierdo -->
      <div class="left-panel">
        <div class="badge-top">📄 Procesamiento inteligente de PDF</div>
        <h1>Sube tu archivo y calcula los 1750 días sin empalmes</h1>
        <p>
          Analiza tu PDF de manera más visual y rápida. El sistema detecta movimientos,
          organiza periodos y elimina fechas empalmadas automáticamente.
        </p>

        <div class="steps">
          <div class="step-card">
            <div class="step-icon">📂</div>
            <div>
              <h6>1. Carga el PDF</h6>
              <p>Arrastra el archivo al área o selecciónalo desde tu equipo.</p>
            </div>
          </div>

          <div class="step-card">
            <div class="step-icon">🔍</div>
            <div>
              <h6>2. Detección automática</h6>
              <p>Busca eventos como REINGRESO y BAJA dentro del documento.</p>
            </div>
          </div>

          <div class="step-card">
            <div class="step-icon">📅</div>
            <div>
              <h6>3. Construcción de periodos</h6>
              <p>Forma periodos válidos y elimina traslapes o empalmes.</p>
            </div>
          </div>

          <div class="step-card">
            <div class="step-icon">✅</div>
            <div>
              <h6>4. Conteo final</h6>
              <p>Calcula los días válidos acumulados hasta llegar al límite de 1750.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel derecho -->
      <div class="right-panel">
        <h3>Subir documento PDF</h3>
        <div class="sub">
          Usa el área de carga inteligente para comenzar el análisis.
        </div>

        <form action="analisis/procesar.php" method="post" enctype="multipart/form-data" id="pdfForm">

          <input
            id="pdf"
            class="hidden-input"
            type="file"
            name="pdf"
            accept="application/pdf"
            required
          >

          <div class="drop-zone" id="dropZone">
            <div class="drop-icon">☁️</div>
            <h5>Arrastra y suelta tu PDF aquí</h5>
            <p>
              o da <span class="browse">clic para seleccionarlo</span>
            </p>
          </div>

          <div class="file-preview" id="filePreview">
            <div class="file-info">
              <div class="file-badge">PDF</div>
              <div class="file-text">
                <div class="file-name" id="fileName">archivo.pdf</div>
                <div class="file-size" id="fileSize">0 KB</div>
              </div>
            </div>

            <button type="button" class="remove-btn" id="removeFile">
              Quitar archivo
            </button>
          </div>

          <div class="status-msg" id="statusMsg"></div>

          <div class="note-box">
            <strong>Importante:</strong>
            solo se permiten archivos en formato PDF. El sistema procesará la información
            para detectar movimientos y contar los días sin empalmes.
          </div>

          <div class="actions">
            <button class="btn-modern btn-submit" type="submit">
              Procesar PDF
            </button>

            <button class="btn-modern btn-reset" type="reset" id="btnReset">
              Limpiar
            </button>

            <a class="btn-modern btn-back" href="/index.php">
              Volver
            </a>
          </div>

        </form>
      </div>

    </div>
  </div>

  <script>
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('pdf');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeFileBtn = document.getElementById('removeFile');
    const btnReset = document.getElementById('btnReset');
    const statusMsg = document.getElementById('statusMsg');
    const form = document.getElementById('pdfForm');

    function formatBytes(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showMessage(message, type = 'ok') {
      statusMsg.textContent = message;
      statusMsg.className = 'status-msg ' + type;
    }

    function clearMessage() {
      statusMsg.textContent = '';
      statusMsg.className = 'status-msg';
    }

    function updatePreview(file) {
      if (!file) return;

      if (file.type !== 'application/pdf') {
        fileInput.value = '';
        filePreview.classList.remove('show');
        showMessage('Solo se permiten archivos PDF.', 'error');
        return;
      }

      fileName.textContent = file.name;
      fileSize.textContent = formatBytes(file.size);
      filePreview.classList.add('show');
      showMessage('Archivo PDF cargado correctamente.', 'ok');
    }

    dropZone.addEventListener('click', () => {
      fileInput.click();
    });

    fileInput.addEventListener('change', (e) => {
      clearMessage();
      const file = e.target.files[0];
      if (file) updatePreview(file);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
      dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('dragover');
      });
    });

    dropZone.addEventListener('drop', (e) => {
      const files = e.dataTransfer.files;
      clearMessage();

      if (files.length > 0) {
        const file = files[0];

        if (file.type !== 'application/pdf') {
          showMessage('Solo se permiten archivos PDF.', 'error');
          return;
        }

        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;

        updatePreview(file);
      }
    });

    removeFileBtn.addEventListener('click', () => {
      fileInput.value = '';
      filePreview.classList.remove('show');
      clearMessage();
    });

    btnReset.addEventListener('click', () => {
      setTimeout(() => {
        filePreview.classList.remove('show');
        clearMessage();
      }, 50);
    });

    form.addEventListener('submit', (e) => {
      if (!fileInput.files.length) {
        e.preventDefault();
        showMessage('Debes seleccionar un archivo PDF antes de procesar.', 'error');
      }
    });
  </script>

</body>
</html>



