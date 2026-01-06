/**
 * Layout Component - Animation Timeline Editor
 * Visual editor for animation keyframes
 */

class AnimationTimelineEditor {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Timeline editor container not found:', containerId);
            return;
        }
        
        this.options = {
            width: options.width || 800,
            height: options.height || 400,
            minKeyframePercent: 0,
            maxKeyframePercent: 100,
            ...options
        };
        
        this.keyframes = options.keyframes || [];
        this.selectedKeyframe = null;
        this.isDragging = false;
        
        this.init();
    }
    
    init() {
        this.render();
        this.attachEvents();
    }
    
    render() {
        this.container.innerHTML = `
            <div class="timeline-editor">
                <div class="timeline-header">
                    <h3>Animation Timeline</h3>
                    <button type="button" class="btn-add-keyframe" onclick="timelineEditor.addKeyframe()">Add Keyframe</button>
                </div>
                <div class="timeline-canvas-container">
                    <canvas id="timeline-canvas" width="${this.options.width}" height="${this.options.height}"></canvas>
                </div>
                <div class="timeline-controls">
                    <div class="keyframe-list" id="keyframe-list"></div>
                    <div class="property-editor" id="property-editor" style="display: none;">
                        <h4>Edit Keyframe</h4>
                        <div class="form-group">
                            <label>Percent:</label>
                            <input type="number" id="keyframe-percent" min="0" max="100" step="1" value="0">
                        </div>
                        <div class="form-group">
                            <label>Properties (JSON):</label>
                            <textarea id="keyframe-properties" rows="6" placeholder='{"opacity": "1", "transform": "translateX(0px)"}'></textarea>
                        </div>
                        <button type="button" class="btn-save-keyframe" onclick="timelineEditor.saveKeyframe()">Save</button>
                        <button type="button" class="btn-delete-keyframe" onclick="timelineEditor.deleteKeyframe()">Delete</button>
                    </div>
                </div>
            </div>
        `;
        
        this.canvas = document.getElementById('timeline-canvas');
        this.ctx = this.canvas.getContext('2d');
        this.keyframeList = document.getElementById('keyframe-list');
        this.propertyEditor = document.getElementById('property-editor');
        
        this.drawTimeline();
        this.updateKeyframeList();
    }
    
    drawTimeline() {
        const ctx = this.ctx;
        const width = this.canvas.width;
        const height = this.canvas.height;
        
        // Clear canvas
        ctx.clearRect(0, 0, width, height);
        
        // Draw background
        ctx.fillStyle = '#f5f5f5';
        ctx.fillRect(0, 0, width, height);
        
        // Draw grid lines (0%, 25%, 50%, 75%, 100%)
        ctx.strokeStyle = '#ddd';
        ctx.lineWidth = 1;
        for (let i = 0; i <= 4; i++) {
            const x = (width / 4) * i;
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, height);
            ctx.stroke();
            
            // Labels
            ctx.fillStyle = '#666';
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';
            ctx.fillText((i * 25) + '%', x, height - 5);
        }
        
        // Draw keyframes
        this.keyframes.forEach((keyframe, index) => {
            const x = (width / 100) * keyframe.percent;
            const y = height / 2;
            
            // Keyframe marker
            ctx.fillStyle = this.selectedKeyframe === index ? '#007bff' : '#28a745';
            ctx.beginPath();
            ctx.arc(x, y, 8, 0, Math.PI * 2);
            ctx.fill();
            
            // Keyframe line
            ctx.strokeStyle = this.selectedKeyframe === index ? '#007bff' : '#28a745';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, height);
            ctx.stroke();
        });
    }
    
    attachEvents() {
        // Canvas click to add/select keyframe
        this.canvas.addEventListener('click', (e) => {
            const rect = this.canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const percent = Math.round((x / this.canvas.width) * 100);
            
            // Check if clicked on existing keyframe
            const clickedKeyframe = this.keyframes.findIndex(kf => {
                const kfX = (this.canvas.width / 100) * kf.percent;
                return Math.abs(x - kfX) < 10;
            });
            
            if (clickedKeyframe >= 0) {
                this.selectKeyframe(clickedKeyframe);
            } else {
                this.addKeyframeAt(percent);
            }
        });
        
        // Canvas drag
        this.canvas.addEventListener('mousedown', (e) => {
            const rect = this.canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const clickedKeyframe = this.keyframes.findIndex(kf => {
                const kfX = (this.canvas.width / 100) * kf.percent;
                return Math.abs(x - kfX) < 10;
            });
            
            if (clickedKeyframe >= 0) {
                this.selectedKeyframe = clickedKeyframe;
                this.isDragging = true;
                this.drawTimeline();
            }
        });
        
        this.canvas.addEventListener('mousemove', (e) => {
            if (this.isDragging && this.selectedKeyframe !== null) {
                const rect = this.canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const percent = Math.max(0, Math.min(100, Math.round((x / this.canvas.width) * 100)));
                this.keyframes[this.selectedKeyframe].percent = percent;
                this.drawTimeline();
                this.updateKeyframeList();
            }
        });
        
        this.canvas.addEventListener('mouseup', () => {
            this.isDragging = false;
        });
    }
    
    addKeyframe() {
        const percent = prompt('Enter keyframe percent (0-100):', '50');
        if (percent !== null) {
            const p = parseInt(percent);
            if (p >= 0 && p <= 100) {
                this.addKeyframeAt(p);
            }
        }
    }
    
    addKeyframeAt(percent) {
        const newKeyframe = {
            percent: percent,
            properties: {}
        };
        
        this.keyframes.push(newKeyframe);
        this.keyframes.sort((a, b) => a.percent - b.percent);
        
        const index = this.keyframes.findIndex(kf => kf.percent === percent);
        this.selectKeyframe(index);
        this.drawTimeline();
        this.updateKeyframeList();
        this.updateFormField();
    }
    
    selectKeyframe(index) {
        this.selectedKeyframe = index;
        const keyframe = this.keyframes[index];
        
        document.getElementById('keyframe-percent').value = keyframe.percent;
        document.getElementById('keyframe-properties').value = JSON.stringify(keyframe.properties, null, 2);
        
        this.propertyEditor.style.display = 'block';
        this.drawTimeline();
    }
    
    saveKeyframe() {
        if (this.selectedKeyframe === null) return;
        
        const percent = parseInt(document.getElementById('keyframe-percent').value);
        const propertiesText = document.getElementById('keyframe-properties').value;
        
        try {
            const properties = JSON.parse(propertiesText);
            this.keyframes[this.selectedKeyframe].percent = percent;
            this.keyframes[this.selectedKeyframe].properties = properties;
            
            this.keyframes.sort((a, b) => a.percent - b.percent);
            this.selectedKeyframe = this.keyframes.findIndex(kf => kf.percent === percent);
            
            this.drawTimeline();
            this.updateKeyframeList();
            this.updateFormField();
        } catch (e) {
            alert('Invalid JSON: ' + e.message);
        }
    }
    
    deleteKeyframe() {
        if (this.selectedKeyframe === null) return;
        
        if (confirm('Delete this keyframe?')) {
            this.keyframes.splice(this.selectedKeyframe, 1);
            this.selectedKeyframe = null;
            this.propertyEditor.style.display = 'none';
            this.drawTimeline();
            this.updateKeyframeList();
            this.updateFormField();
        }
    }
    
    updateKeyframeList() {
        this.keyframeList.innerHTML = '<h4>Keyframes</h4>';
        
        if (this.keyframes.length === 0) {
            this.keyframeList.innerHTML += '<p>No keyframes. Click on timeline to add.</p>';
            return;
        }
        
        this.keyframes.forEach((keyframe, index) => {
            const item = document.createElement('div');
            item.className = 'keyframe-item' + (this.selectedKeyframe === index ? ' selected' : '');
            item.innerHTML = `
                <span>${keyframe.percent}%</span>
                <button type="button" onclick="timelineEditor.selectKeyframe(${index})">Edit</button>
            `;
            this.keyframeList.appendChild(item);
        });
    }
    
    updateFormField() {
        // Update hidden form field with JSON
        const keyframesField = document.getElementById('keyframes');
        if (keyframesField) {
            keyframesField.value = JSON.stringify(this.keyframes);
        }
    }
    
    getKeyframes() {
        return this.keyframes;
    }
    
    setKeyframes(keyframes) {
        this.keyframes = keyframes || [];
        this.keyframes.sort((a, b) => a.percent - b.percent);
        this.drawTimeline();
        this.updateKeyframeList();
        this.updateFormField();
    }
}

// Global instance
let timelineEditor = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const keyframesField = document.getElementById('keyframes');
    if (keyframesField && keyframesField.closest('form')) {
        let initialKeyframes = [];
        try {
            if (keyframesField.value) {
                initialKeyframes = JSON.parse(keyframesField.value);
            }
        } catch (e) {
            console.error('Error parsing keyframes:', e);
        }
        
        timelineEditor = new AnimationTimelineEditor('timeline-editor-container', {
            keyframes: initialKeyframes
        });
    }
});

