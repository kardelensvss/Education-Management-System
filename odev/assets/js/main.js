document.addEventListener('DOMContentLoaded', () => {
    // Add subtle entrance animations to glass panels
    const panels = document.querySelectorAll('.glass-panel');
    panels.forEach((panel, index) => {
        panel.style.opacity = '0';
        panel.style.transform = 'translateY(20px)';
        panel.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            panel.style.opacity = '1';
            panel.style.transform = 'translateY(0)';
        }, 100 * index);
    });
});
