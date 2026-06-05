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
function initTracks() {
    const items = document.querySelectorAll('.track-item');
    tracks = [];
    items.forEach((el, i) => {
        tracks.push({
            id: el.dataset.id,
            src: el.dataset.src,
            title: el.dataset.title,
            artist: el.dataset.artist,
            duration: el.dataset.duration ? parseInt(el.dataset.duration) : null,
            el: el
        });

        // Click to play
        el.addEventListener('click', (e) => {
            if (e.target.closest('.track-delete')) return;
            playIdx(i);
        });

        // Delete button
        const delBtn = el.querySelector('.track-delete');
        if (delBtn) {
            delBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteTrack(el.dataset.id, el);
            });
        }
    });
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
function playIdx(idx) {
    if (idx < 0 || idx >= tracks.length) return;

    currentIdx = idx;
    const t = tracks[idx];

    audio.src = t.src;
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

audio.addEventListener('timeupdate', () => {
    if (!audio.duration) return;
    const pct = (audio.currentTime / audio.duration) * 100;
    document.getElementById('progress-fill').style.width = pct + '%';
    document.getElementById('time-current').textContent = formatTime(audio.currentTime);
    document.getElementById('time-total').textContent = formatTime(audio.duration);
});

// Seek
document.getElementById('progress-track').addEventListener('click', (e) => {
    if (!audio.duration) return;
    const rect = e.currentTarget.getBoundingClientRect();
    const pct = (e.clientX - rect.left) / rect.width;
    audio.currentTime = pct * audio.duration;
});

// Volume
document.getElementById('volume-track').addEventListener('click', (e) => {
    const rect = e.currentTarget.getBoundingClientRect();
    volume = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    audio.volume = volume;
    document.getElementById('volume-fill').style.width = (volume * 100) + '%';
});

// === UI HELPERS ===
function updatePlayUI() {
    const iconPlay = document.getElementById('icon-play');
    const iconPause = document.getElementById('icon-pause');
    iconPlay.style.display = isPlaying ? 'none' : 'block';
    iconPause.style.display = isPlaying ? 'block' : 'none';
}

function updateActiveTrack() {
    document.querySelectorAll('.track-item').forEach((el, i) => {
        el.classList.toggle('playing', i === currentIdx);
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

    // If currently playing this track, stop
    const idx = tracks.findIndex(t => t.id === id);
    if (idx === currentIdx) {
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
    tracks.splice(idx, 1);

    // Reindex currentIdx
    if (idx < currentIdx) currentIdx--;

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

    const newIdx = 0;
    const trackData = { id: String(track.id), src: track.src, title: track.title, artist: track.artist, duration: track.duration, el };
    tracks.unshift(trackData);

    // Bind events
    el.addEventListener('click', (e) => {
        if (e.target.closest('.track-delete')) return;
        playIdx(0); // newly added tracks are at index 0
    });

    el.querySelector('.track-delete').addEventListener('click', (e) => {
        e.stopPropagation();
        deleteTrack(String(track.id), el);
    });

    // Update track count
    const countEl = document.getElementById('track-count');
    if (countEl) {
        const n = tracks.length;
        countEl.textContent = `${n} morceau${n > 1 ? 'x' : ''}`;
    }
}
