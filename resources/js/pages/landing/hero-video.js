export function initHeroVideo(rm) {
    const heroVideo = document.getElementById("hero-bg-video");
    if (!heroVideo) return;

    heroVideo.muted = true;
    heroVideo.volume = 0;

    if (rm) {
        heroVideo.pause();
    } else if (heroVideo.paused) {
        const playAttempt = heroVideo.play();
        if (playAttempt && typeof playAttempt.catch === "function") {
            playAttempt.catch(() => {});
        }
    }
}
