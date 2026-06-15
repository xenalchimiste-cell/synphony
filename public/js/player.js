// === API CONFIG ===
// Utilisé en développement: http://localhost:8000
// En production (Vercel): la variable d'env REACT_APP_API_URL sera utilisée
// Utilise toujours l'origine actuelle du navigateur (bon port automatiquement)
const API_URL =
  typeof process !== "undefined" && process.env && process.env.REACT_APP_API_URL
    ? process.env.REACT_APP_API_URL
    : window.location.origin;

// === MEDIA SESSION API (lecture en arrière-plan sur mobile) ===
// Permet d'afficher les contrôles sur l'écran de verrouillage iOS/Android
function setupMediaSession() {
  if (!("mediaSession" in navigator)) return;

  navigator.mediaSession.setActionHandler("play", () => {
    audio.play().then(() => {
      isPlaying = true;
      updatePlayUI();
      updateMediaSessionState();
    });
  });

  navigator.mediaSession.setActionHandler("pause", () => {
    audio.pause();
    isPlaying = false;
    updatePlayUI();
    updateMediaSessionState();
  });

  navigator.mediaSession.setActionHandler("previoustrack", () => {
    prevTrack();
  });

  navigator.mediaSession.setActionHandler("nexttrack", () => {
    nextTrack();
  });

  navigator.mediaSession.setActionHandler("seekto", (details) => {
    if (details.seekTime && isFinite(audio.duration)) {
      audio.currentTime = details.seekTime;
    }
  });

  navigator.mediaSession.setActionHandler("seekbackward", (details) => {
    const skip = details.seekOffset || 10;
    audio.currentTime = Math.max(0, audio.currentTime - skip);
  });

  navigator.mediaSession.setActionHandler("seekforward", (details) => {
    const skip = details.seekOffset || 10;
    audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + skip);
  });
}

function updateMediaSessionMetadata(t) {
  if (!("mediaSession" in navigator)) return;

  const artwork = [];
  if (t.cover) {
    const coverUrl = t.cover.startsWith("http")
      ? t.cover
      : window.location.origin + t.cover;
    artwork.push(
      { src: coverUrl, sizes: "96x96", type: "image/jpeg" },
      { src: coverUrl, sizes: "128x128", type: "image/jpeg" },
      { src: coverUrl, sizes: "192x192", type: "image/jpeg" },
      { src: coverUrl, sizes: "256x256", type: "image/jpeg" },
      { src: coverUrl, sizes: "384x384", type: "image/jpeg" },
      { src: coverUrl, sizes: "512x512", type: "image/jpeg" }
    );
  }

  navigator.mediaSession.metadata = new MediaMetadata({
    title: t.title || "Titre inconnu",
    artist: t.artist || "Artiste inconnu",
    album: t.album || "Sinphony",
    artwork,
  });
}

function updateMediaSessionState() {
  if (!("mediaSession" in navigator)) return;
  navigator.mediaSession.playbackState = isPlaying ? "playing" : "paused";

  // Mettre à jour la position (pour la barre de progression sur l'écran de verrouillage)
  if (isFinite(audio.duration) && audio.duration > 0) {
    try {
      navigator.mediaSession.setPositionState({
        duration: audio.duration,
        playbackRate: audio.playbackRate,
        position: audio.currentTime,
      });
    } catch (e) {
      // Ignoré si non supporté
    }
  }
}


// === STATE ===
const audio = document.getElementById("audio");
let tracks = []; // [{id, title, artist, duration, src, el}]
let currentIdx = -1;
let isPlaying = false;
let isShuffle = false;
let isRepeat = false;
let volume = 0.7;

audio.volume = volume;
audio.preload = "none"; // Ne pas précharger automatiquement (économise la data mobile)

// Initialiser la Media Session après avoir accès à l'élément audio
setupMediaSession();

// === INIT: collect all playable tracks from DOM ===
function syncTracks() {
  const items = document.querySelectorAll(".playable-track");
  tracks = Array.from(items).map((el) => ({
    id: el.dataset.id,
    src: el.dataset.src,
    title: el.dataset.title,
    artist: el.dataset.artist,
    cover: el.dataset.cover || null,
    duration: el.dataset.duration ? parseInt(el.dataset.duration) : null,
    el,
  }));
}

function getTrackIndexById(id) {
  return tracks.findIndex((t) => t.id === String(id));
}

function onPlayableClick(e) {
  const delBtn = e.target.closest(".track-delete");
  if (delBtn) {
    e.stopPropagation();
    const item = delBtn.closest(".playable-track");
    if (item) deleteTrack(item.dataset.id, item);
    return;
  }

  const item = e.target.closest(".playable-track");
  if (!item) return;

  const idx = getTrackIndexById(item.dataset.id);
  if (idx !== -1) playIdx(idx);
}

function bindPlayableEvents() {
  document.removeEventListener("click", onPlayableClick);
  document.addEventListener("click", onPlayableClick);
}

function initTracks() {
  syncTracks();
  bindPlayableEvents();
}

initTracks();
updatePlayerVisibility(); // Cacher le player au démarrage

// === VIEW SWITCHER ===
// Afficher/masquer le player selon si une musique est en cours
function updatePlayerVisibility() {
  const player = document.getElementById("player");
  if (isPlaying || currentIdx !== -1) {
    player.style.display = "block";
  }
}

function showView(view) {
  const views = ["home", "library", "upload", "search"];

  views.forEach((v) => {
    const el = document.getElementById("view-" + v);
    if (!el) return;
    const active = v === view;
    el.style.display = active ? "block" : "none";
    if (active) {
      el.classList.remove("view-enter");
      void el.offsetWidth;
      el.classList.add("view-enter");
    }
  });

  document
    .getElementById("nav-home")
    .classList.toggle("active", view === "home");
  document
    .getElementById("nav-library")
    .classList.toggle("active", view === "library");
  document
    .getElementById("nav-upload")
    .classList.toggle("active", view === "upload");
  document
    .getElementById("nav-search")
    .classList.toggle("active", view === "search");

  const url = view === "home" ? "/" : `/?view=${view}`;
  history.replaceState({ view }, "", url);

  return false;
}

// Ouvrir la bonne vue depuis l'URL (raccourcis PWA)
const initialView = new URLSearchParams(location.search).get("view");
if (initialView && document.getElementById("view-" + initialView)) {
  showView(initialView);
}

// === PLAYBACK ===
function isSameSrc(trackSrc) {
  if (!audio.src || !trackSrc) return false;
  try {
    const current = new URL(audio.src, window.location.origin).pathname;
    const next = new URL(trackSrc, window.location.origin).pathname;
    return current === next;
  } catch {
    return audio.src.endsWith(trackSrc);
  }
}

function playIdx(idx) {
  if (idx < 0 || idx >= tracks.length) return;

  const t = tracks[idx];

  // Même piste : pause/reprise sans recharger (évite le retour à 0)
  if (currentIdx === idx && isSameSrc(t.src)) {
    if (isPlaying) {
      audio.pause();
      isPlaying = false;
    } else {
      audio
        .play()
        .then(() => {
          isPlaying = true;
        })
        .catch((err) => console.error("Playback error:", err));
    }
    updatePlayUI();
    return;
  }

  currentIdx = idx;
  updatePlayerVisibility();

  if (!isSameSrc(t.src)) {
    audio.src = t.src;
  }

  audio
    .play()
    .then(() => {
      isPlaying = true;
      updatePlayUI();
      updateActiveTrack();
      updatePlayerInfo(t);
      // Mise à jour Media Session pour l'écran de verrouillage
      updateMediaSessionMetadata(t);
      updateMediaSessionState();
    })
    .catch((err) => console.error("Playback error:", err));
}

function togglePlay() {
  if (currentIdx === -1 && tracks.length > 0) {
    playIdx(0);
    return;
  }
  if (isPlaying) {
    audio.pause();
    isPlaying = false;
  } else {
    audio.play().then(() => {
      isPlaying = true;
      updatePlayUI();
    });
    return;
  }
  updatePlayUI();
}

function prevTrack() {
  if (tracks.length === 0) return;
  let idx = currentIdx - 1;
  if (idx < 0) idx = tracks.length - 1;
  playIdx(idx);
}

function nextTrack() {
  if (tracks.length === 0) return;
  let idx;
  if (isShuffle) {
    idx = Math.floor(Math.random() * tracks.length);
  } else {
    idx = currentIdx + 1;
    if (idx >= tracks.length) idx = 0;
  }
  playIdx(idx);
}

function toggleShuffle() {
  isShuffle = !isShuffle;
  document.getElementById("btn-shuffle").classList.toggle("active", isShuffle);
}

function toggleRepeat() {
  isRepeat = !isRepeat;
  document.getElementById("btn-repeat").classList.toggle("active", isRepeat);
}

// === AUDIO EVENTS ===
audio.addEventListener("ended", () => {
  if (isRepeat) {
    audio.currentTime = 0;
    audio.play();
  } else {
    nextTrack();
  }
});

// Mettre à jour la Media Session quand le temps change (pour la barre de progression du lock screen)
// NOTE: la mise à jour périodique est gérée dans le listener timeupdate principal ci-dessous

audio.addEventListener("play", () => {
  isPlaying = true;
  updatePlayUI();
  updateMediaSessionState();
});

audio.addEventListener("pause", () => {
  isPlaying = false;
  updatePlayUI();
  updateMediaSessionState();
});

let isSeeking = false;
let isDraggingVolume = false;

const progressRange = document.getElementById("progress-range");
const timeCurrent = document.getElementById("time-current");

function canSeek() {
  return audio.duration > 0 && isFinite(audio.duration);
}

function seekToPercent(pct) {
  if (!canSeek()) return;

  pct = Math.max(0, Math.min(1, pct));
  const seekTime = Math.min(
    pct * audio.duration,
    Math.max(0, audio.duration - 0.05),
  );

  progressRange.value = Math.round(pct * 1000);
  timeCurrent.textContent = formatTime(seekTime);
  audio.currentTime = seekTime;
}

progressRange.addEventListener("input", () => {
  if (!canSeek()) return;
  isSeeking = true;
  seekToPercent(progressRange.value / 1000);
});

progressRange.addEventListener("change", () => {
  isSeeking = false;
});

audio.addEventListener("timeupdate", () => {
  if (isSeeking) return;
  if (!canSeek()) return;
  const pct = audio.currentTime / audio.duration;
  progressRange.value = Math.round(pct * 1000);
  timeCurrent.textContent = formatTime(audio.currentTime);
  document.getElementById("time-total").textContent = formatTime(
    audio.duration,
  );

  // Mettre à jour la barre de progression du mini player mobile
  const pctDisplay = Math.round(pct * 100);
  document.documentElement.style.setProperty("--mobile-progress", pctDisplay);

  // Mettre à jour la position Media Session toutes les 5 secondes
  if (Math.round(audio.currentTime) % 5 === 0) {
    updateMediaSessionState();
  }
});

// Drag Volume
const volumeTrack = document.getElementById("volume-track");
const volumeFill = document.getElementById("volume-fill");

function setVolumeFromEvent(e) {
  const rect = volumeTrack.getBoundingClientRect();
  if (!rect.width) return volume;
  return Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
}

function setVolumePercent(pct) {
  volume = pct;
  audio.volume = pct;
  volumeFill.style.width = pct * 100 + "%";
}

volumeTrack.addEventListener("pointerdown", (e) => {
  e.preventDefault();
  isDraggingVolume = true;
  volumeTrack.setPointerCapture(e.pointerId);
  setVolumePercent(setVolumeFromEvent(e));
});

volumeTrack.addEventListener("pointermove", (e) => {
  if (!isDraggingVolume) return;
  setVolumePercent(setVolumeFromEvent(e));
});

volumeTrack.addEventListener("pointerup", (e) => {
  if (!isDraggingVolume) return;
  setVolumePercent(setVolumeFromEvent(e));
  isDraggingVolume = false;
  volumeTrack.releasePointerCapture(e.pointerId);
});

volumeTrack.addEventListener("pointercancel", () => {
  isDraggingVolume = false;
});

// === UI HELPERS ===
function updatePlayUI() {
  const iconPlay = document.getElementById("icon-play");
  const iconPause = document.getElementById("icon-pause");
  iconPlay.style.display = isPlaying ? "none" : "block";
  iconPause.style.display = isPlaying ? "block" : "none";

  // Classe is-playing sur le player pour les animations
  const player = document.getElementById("player");
  if (isPlaying) {
    player.classList.add("is-playing");
  } else {
    player.classList.remove("is-playing");
  }
}

function setCoverElement(el, cover, fallbackHtml) {
  if (cover) {
    el.innerHTML = `<img src="${cover}" alt="">`;
  } else {
    el.innerHTML = fallbackHtml;
  }
}

function updateActiveTrack() {
  const currentId =
    currentIdx >= 0 && tracks[currentIdx] ? tracks[currentIdx].id : null;
  document.querySelectorAll(".playable-track").forEach((el) => {
    el.classList.toggle("playing", el.dataset.id === currentId);
  });
}

function updatePlayerInfo(t) {
  document.getElementById("player-title").textContent = t.title;
  document.getElementById("player-artist").textContent = t.artist || "—";

  const iconSvg =
    '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>';
  setCoverElement(
    document.getElementById("player-cover-wrap"),
    t.cover,
    iconSvg,
  );
  setCoverElement(document.getElementById("snp-cover"), t.cover, "");

  const snp = document.getElementById("sidebar-now-playing");
  document.getElementById("snp-title").textContent = t.title;
  document.getElementById("snp-artist").textContent = t.artist || "—";
  snp.style.display = "flex";
}

function formatTime(secs) {
  if (!secs || isNaN(secs)) return "0:00";
  const m = Math.floor(secs / 60);
  const s = Math.floor(secs % 60);
  return `${m}:${s.toString().padStart(2, "0")}`;
}

// === KEYBOARD SHORTCUTS ===
document.addEventListener("keydown", (e) => {
  // Space = play/pause (unless typing in an input)
  if (e.code === "Space" && e.target.tagName !== "INPUT") {
    e.preventDefault();
    togglePlay();
  }
  // Arrow right = +5s
  if (e.code === "ArrowRight" && e.target.tagName !== "INPUT") {
    audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + 5);
  }
  // Arrow left = -5s
  if (e.code === "ArrowLeft" && e.target.tagName !== "INPUT") {
    audio.currentTime = Math.max(0, audio.currentTime - 5);
  }
});

// === DELETE TRACK ===
async function deleteTrack(id, el) {
  if (!confirm("Supprimer ce morceau ?")) return;

  const res = await fetch(`${API_URL}/delete/${id}`, { method: "DELETE" });
  if (!res.ok) return;

  const idx = getTrackIndexById(id);
  const wasPlaying = idx !== -1 && idx === currentIdx;
  const playingId =
    !wasPlaying && currentIdx !== -1 ? tracks[currentIdx]?.id : null;

  if (wasPlaying) {
    audio.pause();
    audio.src = "";
    isPlaying = false;
    currentIdx = -1;
    updatePlayUI();
    document.getElementById("player-title").textContent = "Aucun morceau";
    document.getElementById("player-artist").textContent = "—";
    document.getElementById("sidebar-now-playing").style.display = "none";
  }

  el.remove();
  syncTracks();

  if (!wasPlaying && playingId) {
    currentIdx = getTrackIndexById(playingId);
  }

  // Update count
  const countEl = document.getElementById("track-count");
  if (countEl) {
    const n = tracks.length;
    countEl.textContent = `${n} morceau${n > 1 ? "x" : ""}`;
  }

  // Show empty state if no tracks
  if (tracks.length === 0) {
    const listEl = document.getElementById("track-list");
    if (listEl) {
      listEl.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">🎵</div>
                    <h2>Ta bibliothèque est vide</h2>
                    <p>Ajoute tes fichiers MP3, WAV, OGG ou FLAC<br>et écoute-les hors ligne, sans pub, à l'infini.</p>
                    <button class="btn-primary" onclick="showView('upload')">Ajouter de la musique</button>
                </div>`;
    }
  }
}

// === UPLOAD ===
const uploadZone = document.getElementById("upload-zone");
const fileInput = document.getElementById("file-input");
const uploadQueue = document.getElementById("upload-queue");
const uploadItems = document.getElementById("upload-items");

// Drag & Drop
uploadZone.addEventListener("dragover", (e) => {
  e.preventDefault();
  uploadZone.classList.add("drag-over");
});

uploadZone.addEventListener("dragleave", () =>
  uploadZone.classList.remove("drag-over"),
);

uploadZone.addEventListener("drop", (e) => {
  e.preventDefault();
  uploadZone.classList.remove("drag-over");
  const files = Array.from(e.dataTransfer.files).filter((f) =>
    f.type.includes("audio"),
  );
  if (files.length) uploadFiles(files);
});

// File input
fileInput.addEventListener("change", () => {
  const files = Array.from(fileInput.files);
  if (files.length) uploadFiles(files);
  fileInput.value = "";
});

async function uploadFiles(files) {
  uploadQueue.style.display = "block";

  for (const file of files) {
    const itemEl = document.createElement("div");
    itemEl.className = "upload-item";
    itemEl.innerHTML = `
            <div class="upload-item-name">${file.name}</div>
            <div class="upload-item-status">Envoi en cours...</div>
            <div class="upload-progress-bar"><div class="upload-progress-fill" style="width:0%"></div></div>`;
    uploadItems.appendChild(itemEl);

    const statusEl = itemEl.querySelector(".upload-item-status");
    const progressEl = itemEl.querySelector(".upload-progress-fill");

    try {
      const formData = new FormData();
      formData.append("music", file);

      // Fake progress animation
      let progress = 0;
      const progressInterval = setInterval(() => {
        progress = Math.min(progress + Math.random() * 20, 85);
        progressEl.style.width = progress + "%";
      }, 150);

      const res = await fetch(`${API_URL}/upload`, {
        method: "POST",
        body: formData,
      });
      clearInterval(progressInterval);

      if (!res.ok) {
        const err = await res.json();
        statusEl.textContent = err.error || "Erreur";
        statusEl.className = "upload-item-status error";
        progressEl.style.width = "100%";
        progressEl.style.background = "#e53935";
        continue;
      }

      const track = await res.json();
      progressEl.style.width = "100%";
      statusEl.textContent = "✓ Importé";
      statusEl.className = "upload-item-status done";

      // Add track to library DOM
      addTrackToLibrary(track);
    } catch (err) {
      statusEl.textContent = "Erreur réseau";
      statusEl.className = "upload-item-status error";
    }
  }
}

function addTrackToLibrary(track) {
  // Remove empty state if present
  const emptyState = document.querySelector("#view-library .empty-state");
  if (emptyState) emptyState.remove();

  // Create or get track list
  let listEl = document.getElementById("track-list");
  if (!listEl) {
    listEl = document.createElement("div");
    listEl.className = "track-list";
    listEl.id = "track-list";
    document.getElementById("view-library").appendChild(listEl);
  }

  const colors = [
    "#1db954",
    "#e91e63",
    "#2196f3",
    "#ff9800",
    "#9c27b0",
    "#00bcd4",
  ];
  const color = colors[Math.floor(Math.random() * colors.length)];
  const num = tracks.length + 1;
  const dur = track.duration
    ? `${Math.floor(track.duration / 60)}:${String(track.duration % 60).padStart(2, "0")}`
    : "—";

  const el = document.createElement("div");
  el.className = "track-item playable-track";
  el.dataset.id = track.id;
  el.dataset.src = track.src;
  el.dataset.title = track.title;
  el.dataset.artist = track.artist;
  el.dataset.duration = track.duration || "";
  el.innerHTML = `
        <div class="track-num">${num}</div>
        <div class="track-cover" style="background:${color}22;color:${color}">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
        </div>
        <div class="track-info">
            <span class="track-title">${track.title}</span>
            <span class="track-artist">${track.artist}${track.album ? " • " + track.album : ""}</span>
        </div>
        ${track.genre ? `<span class="track-genre">${track.genre}</span>` : "<span></span>"}
        <span class="track-dur">${dur}</span>
        <button class="track-delete" data-id="${track.id}" title="Supprimer">
            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
        </button>`;

  listEl.prepend(el);
  syncTracks();
  bindPlayableEvents();

  // Update track count
  const countEl = document.getElementById("track-count");
  if (countEl) {
    const n = tracks.length;
    countEl.textContent = `${n} morceau${n > 1 ? "x" : ""}`;
  }
}

// === YOUTUBE DOWNLOAD ===
const youtubeInput = document.getElementById("youtube-url");
const youtubeBtn = document.getElementById("youtube-download-btn");
const youtubeStatus = document.getElementById("youtube-status");

if (youtubeBtn && youtubeInput) {
  youtubeBtn.addEventListener("click", async () => {
    const url = youtubeInput.value.trim();

    if (!url) {
      showYoutubeStatus("error", "❌ Veuillez entrer une URL YouTube");
      return;
    }

    // Vérification basique de l'URL
    if (
      !url.toLowerCase().includes("youtube.com") &&
      !url.toLowerCase().includes("youtu.be")
    ) {
      showYoutubeStatus("error", "❌ URL YouTube invalide");
      return;
    }

    // Désactiver le bouton pendant le téléchargement
    youtubeBtn.disabled = true;
    youtubeBtn.textContent = "⏳ Téléchargement...";
    showYoutubeStatus(
      "loading",
      "⏳ Téléchargement en cours depuis YouTube... Cela peut prendre quelques instants.",
    );

    try {
      const res = await fetch(`${API_URL}/youtube/download`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ url }),
      });

      const data = await res.json();

      if (!res.ok) {
        showYoutubeStatus(
          "error",
          "❌ " + (data.error || "Erreur lors du téléchargement"),
        );
        return;
      }

      if (data.success && data.track) {
        showYoutubeStatus(
          "success",
          `✅ "${data.track.title}" a été ajouté à votre bibliothèque !`,
        );
        youtubeInput.value = "";

        // Ajouter à la bibliothèque
        addTrackToLibrary(data.track);

        // Passer automatiquement à la vue bibliothèque après 2 secondes
        setTimeout(() => {
          showView("library");
        }, 2000);
      } else {
        showYoutubeStatus("error", "❌ Erreur lors de l'ajout du morceau");
      }
    } catch (err) {
      console.error("YouTube download error:", err);
      showYoutubeStatus("error", "❌ Erreur de connexion au serveur");
    } finally {
      youtubeBtn.disabled = false;
      youtubeBtn.textContent = "Télécharger";
    }
  });

  // Appuyer sur Entrée pour télécharger
  youtubeInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      youtubeBtn.click();
    }
  });
}

function showYoutubeStatus(type, message) {
  if (!youtubeStatus) return;

  youtubeStatus.className = type;
  youtubeStatus.textContent = message;
  youtubeStatus.style.display = "block";

  // Masquer automatiquement les messages de succès après 5 secondes
  if (type === "success") {
    setTimeout(() => {
      youtubeStatus.style.display = "none";
    }, 5000);
  }
}

// === SPOTIFY IMPORT ===
const spotifyInput = document.getElementById("spotify-url");
const spotifyBtn = document.getElementById("spotify-download-btn");
const spotifyStatus = document.getElementById("spotify-status");

function showImportStatus(statusEl, type, message) {
  if (!statusEl) return;
  statusEl.className = "import-status " + type;
  statusEl.textContent = message;
  statusEl.style.display = "block";
  if (type === "success") {
    setTimeout(() => {
      statusEl.style.display = "none";
    }, 5000);
  }
}

if (spotifyBtn && spotifyInput) {
  spotifyBtn.addEventListener("click", async () => {
    const url = spotifyInput.value.trim();
    if (!url) {
      showImportStatus(spotifyStatus, "error", "❌ Colle un lien Spotify");
      return;
    }
    if (!url.includes("spotify.com") && !url.includes("spotify:")) {
      showImportStatus(spotifyStatus, "error", "❌ Lien Spotify invalide");
      return;
    }

    spotifyBtn.disabled = true;
    spotifyBtn.textContent = "⏳ Import...";
    showImportStatus(
      spotifyStatus,
      "loading",
      "⏳ Récupération en cours... Cela peut prendre quelques secondes.",
    );

    try {
      const res = await fetch(`${API_URL}/spotify/download`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ url }),
      });

      const data = await res.json();

      if (!res.ok) {
        showImportStatus(
          spotifyStatus,
          "error",
          "❌ " + (data.error || "Erreur"),
        );
        return;
      }

      if (data.tracks) {
        // Playlist ou album
        handlePlaylistResponse(data, spotifyStatus);
        spotifyInput.value = "";
      } else if (data.success && data.track) {
        // Track simple
        showImportStatus(
          spotifyStatus,
          "success",
          `✅ "${data.track.title}" ajouté !`,
        );
        spotifyInput.value = "";
        addTrackToLibrary(data.track);
        setTimeout(() => showView("library"), 2000);
      }
    } catch (err) {
      console.error("Spotify error:", err);
      showImportStatus(
        spotifyStatus,
        "error",
        "❌ " + (err.message || "Erreur de connexion"),
      );
    } finally {
      spotifyBtn.disabled = false;
      spotifyBtn.textContent = "Importer";
    }
  });

  spotifyInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") spotifyBtn.click();
  });
}

// === DEEZER IMPORT ===
const deezerInput = document.getElementById("deezer-url");
const deezerBtn = document.getElementById("deezer-download-btn");
const deezerStatus = document.getElementById("deezer-status");
if (deezerInput) {
  deezerInput.addEventListener("input", () => {
    if (deezerStatus) {
      deezerStatus.style.display = "none";
      deezerStatus.className = "import-status";
      deezerStatus.textContent = "";
    }
  });
}

function handlePlaylistResponse(data, statusEl) {
  if (data.tracks && data.tracks.length > 0) {
    data.tracks.forEach((t) => addTrackToLibrary(t));
    showImportStatus(
      statusEl,
      "success",
      `\u2705 ${data.count} morceau${data.count > 1 ? "x" : ""} ajout\u00e9${data.count > 1 ? "s" : ""} !`,
    );
    setTimeout(() => showView("library"), 2000);
  } else {
    showImportStatus(
      statusEl,
      "error",
      "\u274c Aucun morceau t\u00e9l\u00e9charg\u00e9",
    );
  }
}

if (deezerBtn && deezerInput) {
  deezerBtn.addEventListener("click", async () => {
    const url = deezerInput.value.trim();
    if (!url) {
      showImportStatus(deezerStatus, "error", "\u274c Colle un lien Deezer");
      return;
    }
    if (!url.includes("deezer.com")) {
      showImportStatus(deezerStatus, "error", "\u274c Lien Deezer invalide");
      return;
    }

    const isPlaylist = url.includes("/playlist/") || url.includes("/album/");
    deezerBtn.disabled = true;
    deezerBtn.textContent = "\u23f3 Import...";

    const loadingMsg = isPlaylist
      ? "\u23f3 T\u00e9l\u00e9chargement de la playlist... Cela peut prendre plusieurs minutes."
      : "\u23f3 R\u00e9cup\u00e9ration en cours...";
    showImportStatus(deezerStatus, "loading", loadingMsg);

    try {
      const res = await fetch(`${API_URL}/deezer/download`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ url }),
      });

      // Lire la réponse comme texte d'abord pour voir l'erreur réelle
      const text = await res.text();
      console.log("Deezer raw response:", text.substring(0, 500));

      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        // PHP a retourné du HTML - extraire le message d'erreur
        const match = text.match(/<b>([^<]+)<\/b>:([^<]+)/i);
        const phpError = match
          ? match[1] + ":" + match[2]
          : text
              .replace(/<[^>]+>/g, " ")
              .trim()
              .substring(0, 200);
        showImportStatus(
          deezerStatus,
          "error",
          "\u274c Erreur PHP: " + phpError,
        );
        return;
      }

      if (!res.ok) {
        showImportStatus(
          deezerStatus,
          "error",
          "\u274c " + (data.error || "Erreur"),
        );
        return;
      }

      if (data.tracks) {
        handlePlaylistResponse(data, deezerStatus);
      } else if (data.success && data.job_id) {
        // Téléchargement en arrière-plan - on poll le statut
        deezerInput.value = "";
        showImportStatus(
          deezerStatus,
          "loading",
          `\u23f3 Téléchargement de "${data.title}" en cours...`,
        );
        pollJobStatus(data.job_id, deezerStatus);
      } else if (data.success && data.track) {
        showImportStatus(
          deezerStatus,
          "success",
          `\u2705 "${data.track.title}" ajout\u00e9 !`,
        );
        deezerInput.value = "";
        addTrackToLibrary(data.track);
        setTimeout(() => showView("library"), 2000);
      }
    } catch (err) {
      console.error("Deezer error:", err);
      showImportStatus(
        deezerStatus,
        "error",
        "\u274c " + (err.message || "Erreur de connexion"),
      );
    } finally {
      deezerBtn.disabled = false;
      deezerBtn.textContent = "Importer";
    }
  });

  deezerInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") deezerBtn.click();
  });
}

// === POLL JOB STATUS (Deezer background download) ===
async function pollJobStatus(jobId, statusEl) {
  const maxAttempts = 60; // 5 minutes max
  let attempts = 0;

  const poll = async () => {
    if (attempts >= maxAttempts) {
      showImportStatus(statusEl, "error", "❌ Délai d'attente dépassé");
      return;
    }
    attempts++;

    try {
      const res = await fetch(`${API_URL}/job/${jobId}`);
      if (!res.ok) {
        showImportStatus(statusEl, "error", "❌ Erreur lors du suivi du téléchargement");
        return;
      }
      const data = await res.json();

      if (data.status === "done" && data.track) {
        showImportStatus(statusEl, "success", `✅ "${data.track.title}" ajouté !`);
        addTrackToLibrary(data.track);
        setTimeout(() => showView("library"), 2000);
      } else if (data.status === "error") {
        showImportStatus(statusEl, "error", "❌ " + (data.error || "Erreur de téléchargement"));
      } else {
        // Encore en cours
        setTimeout(poll, 5000);
      }
    } catch (err) {
      showImportStatus(statusEl, "error", "❌ Erreur réseau");
    }
  };

  setTimeout(poll, 3000);
}

// === SEARCH FUNCTIONALITY ===
const searchInput = document.getElementById("search-input");
const searchResults = document.getElementById("search-results");

if (searchInput) {
  searchInput.addEventListener("input", (e) => {
    const query = e.target.value.toLowerCase().trim();

    if (!query) {
      searchResults.innerHTML =
        '<p style="text-align: center; color: #888;">Commence à taper pour chercher</p>';
      return;
    }

    // Filtrer les morceaux disponibles
    const results = tracks.filter(
      (track) =>
        track.title.toLowerCase().includes(query) ||
        track.artist.toLowerCase().includes(query),
    );

    if (results.length === 0) {
      searchResults.innerHTML =
        '<p style="text-align: center; color: #888;">Aucun résultat pour "' +
        query +
        '"</p>';
      return;
    }

    // Afficher les résultats
    searchResults.innerHTML = results
      .map(
        (track) => `
      <div class="search-result-card" onclick="playIdx(${getTrackIndexById(track.id)})">
        <div class="search-result-cover">
          ${track.cover ? '<img src="' + track.cover + '" alt="">' : '<svg viewBox="0 0 24 24" fill="currentColor" width="100%" height="100%" style="background: #282828;"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" fill="#888"/></svg>'}
          <button class="search-result-play" onclick="event.stopPropagation(); playIdx(${getTrackIndexById(track.id)})">
            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M8 5v14l11-7z"/></svg>
          </button>
        </div>
        <div class="search-result-title">${track.title}</div>
        <div class="search-result-artist">${track.artist}</div>
      </div>
    `,
      )
      .join("");
  });
}
