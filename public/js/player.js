// --- Sinphony Music Player & Visualizer Logic ---

document.addEventListener('DOMContentLoaded', () => {
    // --- Elements ---
    const audio = document.getElementById('main-audio');
    const playBtn = document.getElementById('play-btn');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const shuffleBtn = document.getElementById('shuffle-btn');
    const repeatBtn = document.getElementById('repeat-btn');
    
    const progressContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar');
    const currentTimeEl = document.getElementById('current-time');
    const totalTimeEl = document.getElementById('total-time');
    
    const volumeIconBtn = document.getElementById('volume-icon-btn');
    const volumeContainer = document.getElementById('volume-container');
    const volumeSlider = document.getElementById('volume-slider');
    
    const playerCover = document.getElementById('player-cover');
    const playerTitle = document.getElementById('player-title');
    const playerArtist = document.getElementById('player-artist');
    const playerFavBtn = document.getElementById('player-fav-btn');
    
    const searchInput = document.getElementById('search-input');
    const navItems = document.querySelectorAll('.nav-item');
    const tracksContainer = document.getElementById('tracks-list-container');
    const trackRows = Array.from(document.querySelectorAll('.track-row'));
    const trackCountText = document.getElementById('track-count-text');
    
    const canvas = document.getElementById('visualizer-canvas');
    const canvasCtx = canvas.getContext('2d');
    const visualizerOverlay = document.getElementById('visualizer-overlay');
    const toggleVisualizerBtn = document.getElementById('toggle-visualizer-btn');
    const visualizerSection = document.querySelector('.visualizer-section');
    const vizStyleBtns = document.querySelectorAll('.viz-style-btn');
    
    // Hero Elements
    const heroBanner = document.getElementById('hero-banner');
    const heroTitleText = document.getElementById('hero-title-text');
    const heroArtistText = document.getElementById('hero-artist-text');
    const heroAlbumText = document.getElementById('hero-album-text');
    const heroPlayBtn = document.getElementById('hero-play-btn');
    const heroFavBtn = document.getElementById('hero-fav-btn');

    // --- State ---
    let isPlaying = false;
    let isShuffle = false;
    let isRepeat = false;
    let currentTrackIndex = 0;
    let currentView = 'all'; // 'all', 'Lofi', 'Synthwave', 'Cyberpunk', 'favorites'
    let searchQuery = '';
    let favorites = JSON.parse(localStorage.getItem('sinphony_favorites')) || [];
    
    // Audio Context state
    let audioContext = null;
    let analyser = null;
    let dataArray = null;
    let source = null;
    let visualizerStyle = 'bars'; // 'bars', 'wave', 'circle'
    let isVisualizerActive = true;
    let animationId = null;

    // Load active track info initially
    if (trackRows.length > 0) {
        currentTrackIndex = 0;
        setupTrack(trackRows[0], false);
    }
    
    // Setup initial favorites UI states
    updateFavoritesUI();

    // --- Player Control Functions ---

    function setupTrack(row, shouldPlay = true) {
        // Remove active class from all rows
        trackRows.forEach(r => r.classList.remove('playing'));
        
        row.classList.add('playing');
        
        // Extract track meta
        const trackId = row.dataset.trackId;
        const src = row.dataset.src;
        const cover = row.dataset.cover;
        const title = row.dataset.title;
        const artist = row.dataset.artist;
        const album = row.dataset.album;
        
        // Find index in current track list
        currentTrackIndex = trackRows.indexOf(row);
        
        // Set Audio source
        audio.src = src;
        audio.load();
        
        // Update Bottom Player UI
        playerCover.src = cover;
        playerTitle.textContent = title;
        playerArtist.textContent = artist;
        playerFavBtn.dataset.trackId = trackId;
        
        // Update fav icon
        if (favorites.includes(trackId)) {
            playerFavBtn.classList.add('active');
            playerFavBtn.querySelector('i').setAttribute('data-lucide', 'heart-off');
        } else {
            playerFavBtn.classList.remove('active');
            playerFavBtn.querySelector('i').setAttribute('data-lucide', 'heart');
        }
        
        // Update track rows icon state
        trackRows.forEach(r => {
            const playIcon = r.querySelector('.row-play-btn i');
            if (r === row && shouldPlay) {
                playIcon.setAttribute('data-lucide', 'pause');
            } else {
                playIcon.setAttribute('data-lucide', 'play');
            }
        });
        
        lucide.createIcons();

        // If should play
        if (shouldPlay) {
            playAudio();
        } else {
            pauseAudio();
        }
    }

    function playAudio() {
        // Initialize AudioContext on first play
        initAudioContext();
        
        audio.play().then(() => {
            isPlaying = true;
            playBtn.innerHTML = '<i data-lucide="pause"></i>';
            playBtn.querySelector('i').style.fill = '#000';
            
            // Update current row icon
            const activeRow = trackRows[currentTrackIndex];
            if (activeRow) {
                activeRow.classList.add('playing');
                const rowPlay = activeRow.querySelector('.row-play-btn i');
                if (rowPlay) rowPlay.setAttribute('data-lucide', 'pause');
            }
            
            // Hide visualizer overlay
            visualizerOverlay.style.opacity = '0';
            visualizerOverlay.style.pointerEvents = 'none';
            
            // Start visualizer animation loop
            if (isVisualizerActive) {
                startVisualizer();
            }
            
            lucide.createIcons();
        }).catch(err => {
            console.error('Audio play failed:', err);
        });
    }

    function pauseAudio() {
        audio.pause();
        isPlaying = false;
        playBtn.innerHTML = '<i data-lucide="play"></i>';
        playBtn.querySelector('i').style.fill = '#000';
        
        // Update current row icon
        const activeRow = trackRows[currentTrackIndex];
        if (activeRow) {
            const rowPlay = activeRow.querySelector('.row-play-btn i');
            if (rowPlay) rowPlay.setAttribute('data-lucide', 'play');
        }
        
        lucide.createIcons();
    }

    function togglePlay() {
        if (isPlaying) {
            pauseAudio();
        } else {
            playAudio();
        }
    }

    function prevTrack() {
        // Get visible rows
        const visibleRows = trackRows.filter(row => row.style.display !== 'none');
        if (visibleRows.length === 0) return;
        
        let activeVisibleIndex = visibleRows.indexOf(trackRows[currentTrackIndex]);
        let nextVisibleIndex = activeVisibleIndex - 1;
        
        if (nextVisibleIndex < 0) {
            nextVisibleIndex = visibleRows.length - 1;
        }
        
        setupTrack(visibleRows[nextVisibleIndex], true);
    }

    function nextTrack() {
        const visibleRows = trackRows.filter(row => row.style.display !== 'none');
        if (visibleRows.length === 0) return;
        
        let activeVisibleIndex = visibleRows.indexOf(trackRows[currentTrackIndex]);
        let nextVisibleIndex;
        
        if (isShuffle) {
            nextVisibleIndex = Math.floor(Math.random() * visibleRows.length);
        } else {
            nextVisibleIndex = activeVisibleIndex + 1;
            if (nextVisibleIndex >= visibleRows.length) {
                nextVisibleIndex = 0;
            }
        }
        
        setupTrack(visibleRows[nextVisibleIndex], true);
    }

    // --- Audio Event Listeners ---

    audio.addEventListener('timeupdate', () => {
        if (audio.duration) {
            const current = audio.currentTime;
            const duration = audio.duration;
            const progressPercent = (current / duration) * 100;
            
            progressBar.style.width = `${progressPercent}%`;
            
            // Time displays
            currentTimeEl.textContent = formatTime(current);
            totalTimeEl.textContent = formatTime(duration);
        }
    });

    audio.addEventListener('ended', () => {
        if (isRepeat) {
            audio.currentTime = 0;
            playAudio();
        } else {
            nextTrack();
        }
    });

    // Seek Timeline
    progressContainer.addEventListener('click', (e) => {
        const width = progressContainer.clientWidth;
        const clickX = e.offsetX;
        const duration = audio.duration;
        
        if (duration) {
            audio.currentTime = (clickX / width) * duration;
        }
    });

    // Volume Slider Control
    volumeContainer.addEventListener('click', (e) => {
        const width = volumeContainer.clientWidth;
        const clickX = e.offsetX;
        let volume = clickX / width;
        
        if (volume < 0) volume = 0;
        if (volume > 1) volume = 1;
        
        audio.volume = volume;
        volumeSlider.style.width = `${volume * 100}%`;
        
        // Update icon based on volume level
        updateVolumeIcon(volume);
    });

    function updateVolumeIcon(vol) {
        const icon = volumeIconBtn.querySelector('i');
        if (vol === 0) {
            icon.setAttribute('data-lucide', 'volume-x');
        } else if (vol < 0.4) {
            icon.setAttribute('data-lucide', 'volume');
        } else if (vol < 0.75) {
            icon.setAttribute('data-lucide', 'volume-1');
        } else {
            icon.setAttribute('data-lucide', 'volume-2');
        }
        lucide.createIcons();
    }

    // Toggle Mute
    let lastVolume = 0.7;
    volumeIconBtn.addEventListener('click', () => {
        if (audio.volume > 0) {
            lastVolume = audio.volume;
            audio.volume = 0;
            volumeSlider.style.width = '0%';
            updateVolumeIcon(0);
        } else {
            audio.volume = lastVolume;
            volumeSlider.style.width = `${lastVolume * 100}%`;
            updateVolumeIcon(lastVolume);
        }
    });

    // Timeline helpers
    function formatTime(seconds) {
        const min = Math.floor(seconds / 60);
        const sec = Math.floor(seconds % 60);
        return `${min.toString().padStart(2, '0')}:${sec.toString().padStart(2, '0')}`;
    }

    // --- Controls Listeners ---
    playBtn.addEventListener('click', togglePlay);
    prevBtn.addEventListener('click', prevTrack);
    nextBtn.addEventListener('click', nextTrack);

    shuffleBtn.addEventListener('click', () => {
        isShuffle = !isShuffle;
        shuffleBtn.classList.toggle('active', isShuffle);
    });

    repeatBtn.addEventListener('click', () => {
        isRepeat = !isRepeat;
        repeatBtn.classList.toggle('active', isRepeat);
    });

    // Click track rows to play
    trackRows.forEach(row => {
        row.addEventListener('click', (e) => {
            // If click was on favorite button, don't trigger play
            if (e.target.closest('.row-fav-btn')) return;
            
            setupTrack(row, true);
            updateHeroBanner(row);
        });
    });

    // Play now button on featured hero
    if (heroPlayBtn) {
        heroPlayBtn.addEventListener('click', () => {
            const trackId = heroPlayBtn.dataset.trackId;
            const targetRow = trackRows.find(r => r.dataset.trackId === trackId);
            if (targetRow) {
                setupTrack(targetRow, true);
            }
        });
    }

    // --- Favorites Logic ---

    // Toggle favorite state
    function toggleFavorite(trackId) {
        trackId = String(trackId);
        const index = favorites.indexOf(trackId);
        if (index === -1) {
            favorites.push(trackId);
        } else {
            favorites.splice(index, 1);
        }
        localStorage.setItem('sinphony_favorites', JSON.stringify(favorites));
        
        // Update UI
        updateFavoritesUI();
        
        // If viewing favorites, filter instantly
        if (currentView === 'favorites') {
            filterTracks();
        }
    }

    function updateFavoritesUI() {
        // Update all favorite buttons
        document.querySelectorAll('.row-fav-btn, #player-fav-btn, #hero-fav-btn').forEach(btn => {
            const id = String(btn.dataset.trackId);
            if (favorites.includes(id)) {
                btn.classList.add('active');
                const icon = btn.querySelector('i');
                if (icon) icon.setAttribute('data-lucide', 'heart-off');
            } else {
                btn.classList.remove('active');
                const icon = btn.querySelector('i');
                if (icon) icon.setAttribute('data-lucide', 'heart');
            }
        });
        lucide.createIcons();
    }

    // Listen to favorite buttons clicks
    document.addEventListener('click', (e) => {
        const favBtn = e.target.closest('.row-fav-btn, #player-fav-btn, #hero-fav-btn');
        if (favBtn) {
            e.stopPropagation();
            const trackId = favBtn.dataset.trackId;
            if (trackId) {
                toggleFavorite(trackId);
            }
        }
    });

    // --- Filter & Views Logic ---

    // Sidebar navigation filtering
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            navItems.forEach(n => n.classList.remove('active'));
            item.classList.add('active');
            
            currentView = item.dataset.view;
            filterTracks();
        });
    });

    // Realtime search input
    searchInput.addEventListener('input', (e) => {
        searchQuery = e.target.value.toLowerCase().trim();
        filterTracks();
    });

    function filterTracks() {
        let visibleCount = 0;
        
        trackRows.forEach(row => {
            const title = row.dataset.title.toLowerCase();
            const artist = row.dataset.artist.toLowerCase();
            const album = row.dataset.album.toLowerCase();
            const genre = row.dataset.genre;
            const trackId = String(row.dataset.trackId);
            
            let matchesView = false;
            
            if (currentView === 'all') {
                matchesView = true;
            } else if (currentView === 'favorites') {
                matchesView = favorites.includes(trackId);
            } else {
                // Genre match
                matchesView = (genre === currentView);
            }
            
            let matchesSearch = true;
            if (searchQuery !== '') {
                matchesSearch = title.includes(searchQuery) || 
                                artist.includes(searchQuery) || 
                                album.includes(searchQuery);
            }
            
            if (matchesView && matchesSearch) {
                row.style.display = 'flex';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update track count text
        if (currentView === 'favorites') {
            trackCountText.textContent = `${visibleCount} favori(s) enregistré(s)`;
        } else if (currentView === 'all') {
            trackCountText.textContent = `${visibleCount} morceaux en base de données`;
        } else {
            trackCountText.textContent = `${visibleCount} morceau(x) genre ${currentView}`;
        }
    }

    function updateHeroBanner(row) {
        if (!heroBanner) return;
        
        const trackId = row.dataset.trackId;
        const cover = row.dataset.cover;
        const title = row.dataset.title;
        const artist = row.dataset.artist;
        const album = row.dataset.album;
        
        heroBanner.style.backgroundImage = `linear-gradient(to right, rgba(13, 11, 26, 0.95), rgba(13, 11, 26, 0.35)), url('${cover}')`;
        heroTitleText.textContent = title;
        heroArtistText.innerHTML = `${artist} • <span class="album-tag">${album}</span>`;
        
        heroPlayBtn.dataset.trackId = trackId;
        heroFavBtn.dataset.trackId = trackId;
        
        // Update fav state
        updateFavoritesUI();
    }

    // --- Web Audio API & Visualizer Logic ---

    function initAudioContext() {
        if (audioContext) return;
        
        try {
            // Setup Web Audio nodes
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            analyser.fftSize = 256; // 128 frequency bins
            
            source = audioContext.createMediaElementSource(audio);
            source.connect(analyser);
            analyser.connect(audioContext.destination);
            
            const bufferLength = analyser.frequencyBinCount;
            dataArray = new Uint8Array(bufferLength);
        } catch (err) {
            console.error('Failed to initialize Web Audio API:', err);
        }
    }

    function startVisualizer() {
        if (!analyser) return;
        
        // Resize canvas to its container size
        resizeCanvas();
        
        // Cancel previous animation frame if exists
        if (animationId) {
            cancelAnimationFrame(animationId);
        }
        
        renderFrame();
    }

    function renderFrame() {
        animationId = requestAnimationFrame(renderFrame);
        
        if (!isPlaying || !isVisualizerActive) return;
        
        analyser.getByteFrequencyData(dataArray);
        
        // Clear canvas
        canvasCtx.fillStyle = 'rgba(10, 9, 20, 0.25)'; // trail effect
        canvasCtx.fillRect(0, 0, canvas.width, canvas.height);
        
        const bufferLength = analyser.frequencyBinCount;
        
        if (visualizerStyle === 'bars') {
            const barWidth = (canvas.width / bufferLength) * 1.5;
            let barHeight;
            let x = 0;
            
            for (let i = 0; i < bufferLength; i++) {
                barHeight = dataArray[i] * 0.9;
                
                // Color gradient
                const percent = i / bufferLength;
                const r = Math.floor(79 + percent * 176); // indigo to magenta
                const g = Math.floor(70 - percent * 70);
                const b = Math.floor(229 + percent * 26);
                
                canvasCtx.fillStyle = `rgb(${r}, ${g}, ${b})`;
                
                // Rounded bars
                canvasCtx.fillRect(x, canvas.height - barHeight, barWidth - 2, barHeight);
                x += barWidth;
            }
        } 
        else if (visualizerStyle === 'wave') {
            analyser.getByteTimeDomainData(dataArray);
            
            canvasCtx.lineWidth = 3;
            
            // Gradient line
            const grad = canvasCtx.createLinearGradient(0, 0, canvas.width, 0);
            grad.addColorStop(0, '#00f2fe');
            grad.addColorStop(0.5, '#7b2cbf');
            grad.addColorStop(1, '#ff007f');
            canvasCtx.strokeStyle = grad;
            
            canvasCtx.beginPath();
            
            const sliceWidth = canvas.width / bufferLength;
            let x = 0;
            
            for (let i = 0; i < bufferLength; i++) {
                const v = dataArray[i] / 128.0;
                const y = (v * canvas.height) / 2;
                
                if (i === 0) {
                    canvasCtx.moveTo(x, y);
                } else {
                    canvasCtx.lineTo(x, y);
                }
                
                x += sliceWidth;
            }
            
            canvasCtx.lineTo(canvas.width, canvas.height / 2);
            canvasCtx.stroke();
        } 
        else if (visualizerStyle === 'circle') {
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const baseRadius = Math.min(canvas.width, canvas.height) * 0.22;
            
            // Draw background glow
            let sum = 0;
            for (let i = 0; i < bufferLength; i++) sum += dataArray[i];
            const avg = sum / bufferLength;
            
            // Draw pulse radial gradient
            const glowGrad = canvasCtx.createRadialGradient(centerX, centerY, baseRadius * 0.5, centerX, centerY, baseRadius + avg * 0.8);
            glowGrad.addColorStop(0, 'rgba(123, 44, 191, 0.15)');
            glowGrad.addColorStop(0.6, 'rgba(0, 242, 254, 0.05)');
            glowGrad.addColorStop(1, 'rgba(0, 0, 0, 0)');
            canvasCtx.fillStyle = glowGrad;
            canvasCtx.beginPath();
            canvasCtx.arc(centerX, centerY, baseRadius + avg * 0.8, 0, 2 * Math.PI);
            canvasCtx.fill();
            
            // Draw inner circle
            canvasCtx.beginPath();
            canvasCtx.arc(centerX, centerY, baseRadius, 0, 2 * Math.PI);
            canvasCtx.strokeStyle = 'rgba(255, 255, 255, 0.08)';
            canvasCtx.lineWidth = 2;
            canvasCtx.stroke();
            
            // Draw radial frequency lines
            const numLines = 80;
            for (let i = 0; i < numLines; i++) {
                // Map line to data frequency index
                const dataIndex = Math.floor((i / numLines) * bufferLength);
                const val = dataArray[dataIndex];
                const lineLength = val * 0.45;
                
                const angle = (i / numLines) * 2 * Math.PI;
                const startX = centerX + Math.cos(angle) * baseRadius;
                const startY = centerY + Math.sin(angle) * baseRadius;
                const endX = centerX + Math.cos(angle) * (baseRadius + lineLength);
                const endY = centerY + Math.sin(angle) * (baseRadius + lineLength);
                
                // Color based on index
                const hue = (i / numLines) * 360;
                canvasCtx.strokeStyle = `hsla(${hue}, 85%, 65%, 0.85)`;
                canvasCtx.lineWidth = 3;
                canvasCtx.beginPath();
                canvasCtx.moveTo(startX, startY);
                canvasCtx.lineTo(endX, endY);
                canvasCtx.stroke();
            }
        }
    }

    function resizeCanvas() {
        const container = canvas.parentElement;
        canvas.width = container.clientWidth;
        canvas.height = container.clientHeight;
    }

    // Resize listener
    window.addEventListener('resize', () => {
        if (animationId) {
            resizeCanvas();
        }
    });

    // Style switchers
    vizStyleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            vizStyleBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            visualizerStyle = btn.dataset.style;
        });
    });

    // Hide/Show visualizer completely
    toggleVisualizerBtn.addEventListener('click', () => {
        isVisualizerActive = !isVisualizerActive;
        toggleVisualizerBtn.classList.toggle('active', isVisualizerActive);
        
        if (isVisualizerActive) {
            visualizerSection.style.display = 'flex';
            document.querySelector('.dashboard-grid').style.gridTemplateColumns = '1.1fr 0.9fr';
            // Trigger start
            setTimeout(() => {
                resizeCanvas();
                if (isPlaying) startVisualizer();
            }, 100);
        } else {
            visualizerSection.style.display = 'none';
            document.querySelector('.dashboard-grid').style.gridTemplateColumns = '1fr';
            if (animationId) {
                cancelAnimationFrame(animationId);
                animationId = null;
            }
        }
    });
});
