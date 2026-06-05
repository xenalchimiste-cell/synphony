// === STATE ===
const audio = document.getElementById('audio');
let tracks = [];        // [{id, title, artist, duration, src, el}]
let currentIdx = -1;
let isPlaying = false;
let isShuffle = false;
let isRepeat = false;
let volume = 0.7;

audio.volume = volume;

// === INIT: collect all track items from DOM ===
function syncTracks() {
    const items = document.querySelectorAll('.track-item');
    tracks = Array.from(items).map(el => ({
        id: el.dataset.id,
        src: el.dataset.src,
        title: el.dataset.title,
        artist: el.dataset.artist,
        duration: el.dataset.duration ? parseInt(el.dataset.duration) : null,
        el
    }));
}

function getTrackIndexById(id) {
    return tracks.findIndex(t => t.id === String(id));
}

function onTrackListClick(e) {
    const delBtn = e.target.closest('.track-delete');
    if (delBtn) {
        e.stopPropagation();
        const item = delBtn.closest('.track-item');
        if (item) deleteTrack(item.dataset.id, item);
        return;
    }

    const item = e.target.closest('.track-item');
    if (!item) return;

    const idx = getTrackIndexById(item.dataset.id);
    if (idx !== -1) playIdx(idx);
}

function bindTrackListEvents() {
    const listEl = document.getElementById('track-list');
    if (!listEl) return;
    listEl.removeEventListener('click', onTrackListClick);
    listEl.addEventListener('click', onTrackListClick);
}

function initTracks() {
    syncTracks();
    bindTrackListEvents();
}

initTracks();

// === VIEW SWITCHER ===
function showView(view) {
    document.getElementById('view-library').style.display = view === 'library' ? 'block' : 'none';
    document.getElementById('view-upload').style.display = view === 'upload' ? 'block' : 'none';

    document.getElementById('nav-library').classList.toggle('active', view === 'library');
    document.getElementById('nav-upload').classList.toggle('active', view === 'upload');

    return false;
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
            audio.play().then(() => { isPlaying = true; }).catch(err => console.error('Playback error:', err));
        }
        updatePlayUI();
        return;
    }

    currentIdx = idx;

    if (!isSameSrc(t.src)) {
        audio.src = t.src;
    }

    audio.play().then(() => {
        isPlaying = true;
        updatePlayUI();
        updateActiveTrack();
        updatePlayerInfo(t);
    }).catch(err => console.error('Playback error:', err));
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
        audio.play().then(() => { isPlaying = true; updatePlayUI(); });
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
    document.getElementById('btn-shuffle').classList.toggle('active', isShuffle);
}

function toggleRepeat() {
    isRepeat = !isRepeat;
    document.getElementById('btn-repeat').classList.toggle('active', isRepeat);
}

// === AUDIO EVENTS ===
audio.addEventListener('ended', () => {
    if (isRepeat) {
        audio.currentTime = 0;
        audio.play();
    } else {
        nextTrack();
    }
});

let isDraggingTime = false;
let isDraggingVolume = false;
let pendingSeekPct = null;
let seekFinishTimer = null;

const progressTrack = document.getElementById('progress-track');
const progressFill = document.getElementById('progress-fill');
const timeCurrent = document.getElementById('time-current');

function setTimeFromEvent(e) {
    const rect = progressTrack.getBoundingClientRect();
    if (!rect.width) return 0;
    return Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
}

function getMaxSeekTime() {
    if (!audio.duration || !isFinite(audio.duration)) return 0;
    if (audio.seekable && audio.seekable.length > 0) {
        return audio.seekable.end(audio.seekable.length - 1);
    }
    return audio.duration;
}

function updateProgressUI(pct) {
    if (!audio.duration || !isFinite(audio.duration)) return;
    progressFill.style.width = (pct * 100) + '%';
    timeCurrent.textContent = formatTime(pct * audio.duration);
}

function finishSeek() {
    pendingSeekPct = null;
    isDraggingTime = false;
    progressTrack.classList.remove('is-seeking');
    if (seekFinishTimer) {
        clearTimeout(seekFinishTimer);
        seekFinishTimer = null;
    }
}

function seekToPercent(pct) {
    const maxTime = getMaxSeekTime();
    if (!maxTime) return;

    pct = Math.max(0, Math.min(1, pct));
    pendingSeekPct = pct;
    progressTrack.classList.add('is-seeking');
    updateProgressUI(pct);

    const seekTime = Math.min(pct * maxTime, Math.max(0, maxTime - 0.01));
    audio.currentTime = seekTime;

    if (seekFinishTimer) clearTimeout(seekFinishTimer);
    seekFinishTimer = setTimeout(finishSeek, 400);
}

progressTrack.addEventListener('pointerdown', (e) => {
    if (!getMaxSeekTime()) return;
    e.preventDefault();
    isDraggingTime = true;
    progressTrack.setPointerCapture(e.pointerId);
    seekToPercent(setTimeFromEvent(e));
});

progressTrack.addEventListener('pointermove', (e) => {
    if (!isDraggingTime) return;
    seekToPercent(setTimeFromEvent(e));
});

progressTrack.addEventListener('pointerup', (e) => {
    if (!isDraggingTime) return;
    seekToPercent(setTimeFromEvent(e));
    try { progressTrack.releasePointerCapture(e.pointerId); } catch (_) {}
});

progressTrack.addEventListener('pointercancel', () => {
    finishSeek();
});

audio.addEventListener('timeupdate', () => {
    if (isDraggingTime || pendingSeekPct !== null) return;
    if (!audio.duration || !isFinite(audio.duration)) return;
    const pct = (audio.currentTime / audio.duration) * 100;
    progressFill.style.width = pct + '%';
    timeCurrent.textContent = formatTime(audio.currentTime);
    document.getElementById('time-total').textContent = formatTime(audio.duration);
});

audio.addEventListener('seeked', finishSeek);

// Drag Volume
const volumeTrack = document.getElementById('volume-track');
const volumeFill = document.getElementById('volume-fill');

function setVolumeFromEvent(e) {
    const rect = volumeTrack.getBoundingClientRect();
    if (!rect.width) return volume;
    return Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
}

function setVolumePercent(pct) {
    volume = pct;
    audio.volume = pct;
    volumeFill.style.width = (pct * 100) + '%';
}

volumeTrack.addEventListener('pointerdown', (e) => {
    e.preventDefault();
    isDraggingVolume = true;
    volumeTrack.setPointerCapture(e.pointerId);
    setVolumePercent(setVolumeFromEvent(e));
});

volumeTrack.addEventListener('pointermove', (e) => {
    if (!isDraggingVolume) return;
    setVolumePercent(setVolumeFromEvent(e));
});

volumeTrack.addEventListener('pointerup', (e) => {
    if (!isDraggingVolume) return;
    setVolumePercent(setVolumeFromEvent(e));
    isDraggingVolume = false;
    volumeTrack.releasePointerCapture(e.pointerId);
});

volumeTrack.addEventListener('pointercancel', () => {
    isDraggingVolume = false;
});

// === UI HELPERS ===
function updatePlayUI() {
    const iconPlay = document.getElementById('icon-play');
    const iconPause = document.getElementById('icon-pause');
    iconPlay.style.display = isPlaying ? 'none' : 'block';
    iconPause.style.display = isPlaying ? 'block' : 'none';
}

function updateActiveTrack() {
    const currentId = currentIdx >= 0 && tracks[currentIdx] ? tracks[currentIdx].id : null;
    document.querySelectorAll('.track-item').forEach(el => {
        el.classList.toggle('playing', el.dataset.id === currentId);
    });
}

function updatePlayerInfo(t) {
    document.getElementById('player-title').textContent = t.title;
    document.getElementById('player-artist').textContent = t.artist || '—';

    // Sidebar now playing
    const snp = document.getElementById('sidebar-now-playing');
    document.getElementById('snp-title').textContent = t.title;
    document.getElementById('snp-artist').textContent = t.artist || '—';
    snp.style.display = 'flex';
}

function formatTime(secs) {
    if (!secs || isNaN(secs)) return '0:00';
    const m = Math.floor(secs / 60);
    const s = Math.floor(secs % 60);
    return `${m}:${s.toString().padStart(2, '0')}`;
}

// === KEYBOARD SHORTCUTS ===
document.addEventListener('keydown', (e) => {
    // Space = play/pause (unless typing in an input)
    if (e.code === 'Space' && e.target.tagName !== 'INPUT') {
        e.preventDefault();
        togglePlay();
    }
    // Arrow right = +5s
    if (e.code === 'ArrowRight' && e.target.tagName !== 'INPUT') {
        audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + 5);
    }
    // Arrow left = -5s
    if (e.code === 'ArrowLeft' && e.target.tagName !== 'INPUT') {
        audio.currentTime = Math.max(0, audio.currentTime - 5);
    }
});

// === DELETE TRACK ===
async function deleteTrack(id, el) {
    if (!confirm('Supprimer ce morceau ?')) return;

    const res = await fetch(`/delete/${id}`, { method: 'DELETE' });
    if (!res.ok) return;

    const idx = getTrackIndexById(id);
    const wasPlaying = idx !== -1 && idx === currentIdx;
    const playingId = !wasPlaying && currentIdx !== -1 ? tracks[currentIdx]?.id : null;

    if (wasPlaying) {
        audio.pause();
        audio.src = '';
        isPlaying = false;
        currentIdx = -1;
        updatePlayUI();
        document.getElementById('player-title').textContent = 'Aucun morceau';
        document.getElementById('player-artist').textContent = '—';
        document.getElementById('sidebar-now-playing').style.display = 'none';
    }

    el.remove();
    syncTracks();

    if (!wasPlaying && playingId) {
        currentIdx = getTrackIndexById(playingId);
    }

    // Update count
    const countEl = document.getElementById('track-count');
    if (countEl) {
        const n = tracks.length;
        countEl.textContent = `${n} morceau${n > 1 ? 'x' : ''}`;
    }

    // Show empty state if no tracks
    if (tracks.length === 0) {
        const listEl = document.getElementById('track-list');
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
const uploadZone = document.getElementById('upload-zone');
const fileInput = document.getElementById('file-input');
const uploadQueue = document.getElementById('upload-queue');
const uploadItems = document.getElementById('upload-items');

// Drag & Drop
uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('drag-over');
});

uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));

uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    const files = Array.from(e.dataTransfer.files).filter(f => f.type.includes('audio'));
    if (files.length) uploadFiles(files);
});

// File input
fileInput.addEventListener('change', () => {
    const files = Array.from(fileInput.files);
    if (files.length) uploadFiles(files);
    fileInput.value = '';
});

async function uploadFiles(files) {
    uploadQueue.style.display = 'block';

    for (const file of files) {
        const itemEl = document.createElement('div');
        itemEl.className = 'upload-item';
        itemEl.innerHTML = `
            <div class="upload-item-name">${file.name}</div>
            <div class="upload-item-status">Envoi en cours...</div>
            <div class="upload-progress-bar"><div class="upload-progress-fill" style="width:0%"></div></div>`;
        uploadItems.appendChild(itemEl);

        const statusEl = itemEl.querySelector('.upload-item-status');
        const progressEl = itemEl.querySelector('.upload-progress-fill');

        try {
            const formData = new FormData();
            formData.append('music', file);

            // Fake progress animation
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress = Math.min(progress + Math.random() * 20, 85);
                progressEl.style.width = progress + '%';
            }, 150);

            const res = await fetch('/upload', { method: 'POST', body: formData });
            clearInterval(progressInterval);

            if (!res.ok) {
                const err = await res.json();
                statusEl.textContent = err.error || 'Erreur';
                statusEl.className = 'upload-item-status error';
                progressEl.style.width = '100%';
                progressEl.style.background = '#e53935';
                continue;
            }

            const track = await res.json();
            progressEl.style.width = '100%';
            statusEl.textContent = '✓ Importé';
            statusEl.className = 'upload-item-status done';

            // Add track to library DOM
            addTrackToLibrary(track);

        } catch (err) {
            statusEl.textContent = 'Erreur réseau';
            statusEl.className = 'upload-item-status error';
        }
    }
}

function addTrackToLibrary(track) {
    // Remove empty state if present
    const emptyState = document.querySelector('#view-library .empty-state');
    if (emptyState) emptyState.remove();

    // Create or get track list
    let listEl = document.getElementById('track-list');
    if (!listEl) {
        listEl = document.createElement('div');
        listEl.className = 'track-list';
        listEl.id = 'track-list';
        document.getElementById('view-library').appendChild(listEl);
    }

    const colors = ['#1db954','#e91e63','#2196f3','#ff9800','#9c27b0','#00bcd4'];
    const color = colors[Math.floor(Math.random() * colors.length)];
    const num = tracks.length + 1;
    const dur = track.duration ? `${Math.floor(track.duration/60)}:${String(track.duration%60).padStart(2,'0')}` : '—';

    const el = document.createElement('div');
    el.className = 'track-item';
    el.dataset.id = track.id;
    el.dataset.src = track.src;
    el.dataset.title = track.title;
    el.dataset.artist = track.artist;
    el.dataset.duration = track.duration || '';
    el.innerHTML = `
        <div class="track-num">${num}</div>
        <div class="track-cover" style="background:${color}22;color:${color}">
            <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
        </div>
        <div class="track-info">
            <span class="track-title">${track.title}</span>
            <span class="track-artist">${track.artist}${track.album ? ' • ' + track.album : ''}</span>
        </div>
        ${track.genre ? `<span class="track-genre">${track.genre}</span>` : '<span></span>'}
        <span class="track-dur">${dur}</span>
        <button class="track-delete" data-id="${track.id}" title="Supprimer">
            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
        </button>`;

    listEl.prepend(el);
    syncTracks();
    bindTrackListEvents();

    // Update track count
    const countEl = document.getElementById('track-count');
    if (countEl) {
        const n = tracks.length;
        countEl.textContent = `${n} morceau${n > 1 ? 'x' : ''}`;
    }
}
