<div class="pedigree-chart-container">
    <div class="flex items-center gap-3 mb-4">
        <label class="text-sm text-gray-700">Root person:</label>
        <select class="fi-input block rounded-md border-gray-300 text-sm"
                wire:change="setRootPerson($event.target.value)">
            @foreach($this->peopleList as $id => $label)
                <option value="{{ $id }}" @selected($rootPersonId === (int) $id)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="chart-header mb-4">
        <h3 class="text-xl font-semibold text-gray-800">Pedigree Chart</h3>
        <p class="text-xs text-gray-500 mt-1">Drag inside the chart to pan. Use generation buttons to resize ancestry depth.</p>
        <p class="text-xs text-blue-700 mt-1">
            Showing {{ $generations }} generation{{ $generations === 1 ? '' : 's' }} with {{ $visibleNodeCount }} visible people.
        </p>
        <div class="chart-controls flex gap-2 mt-2">
            <button wire:click="setGenerations(2)" class="px-3 py-1 bg-blue-500 text-white rounded {{ $generations == 2 ? 'bg-blue-700' : '' }}">2 Gen</button>
            <button wire:click="setGenerations(3)" class="px-3 py-1 bg-blue-500 text-white rounded {{ $generations == 3 ? 'bg-blue-700' : '' }}">3 Gen</button>
            <button wire:click="setGenerations(4)" class="px-3 py-1 bg-blue-500 text-white rounded {{ $generations == 4 ? 'bg-blue-700' : '' }}">4 Gen</button>
            <button wire:click="setGenerations(5)" class="px-3 py-1 bg-blue-500 text-white rounded {{ $generations == 5 ? 'bg-blue-700' : '' }}">5 Gen</button>
            <button wire:click="toggleDates" class="px-3 py-1 bg-gray-500 text-white rounded {{ $showDates ? 'bg-gray-700' : '' }}">{{ $showDates ? 'Hide' : 'Show' }} Dates</button>
        </div>
    </div>

    <div id="pedigree-chart-display" class="chart-display bg-white border rounded-lg p-4" style="min-height: 500px; overflow: hidden; cursor: grab;">
        @if(!empty($tree))
            <div class="pedigree-tree">
                {!! $this->renderPedigreeTree($tree) !!}
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-gray-400 text-6xl mb-4">👥</div>
                <h4 class="text-lg font-medium text-gray-600 mb-2">No Family Data Available</h4>
                <p class="text-gray-500">Add people to your family tree to see the pedigree chart.</p>
            </div>
        @endif
    </div>

<style>
.pedigree-tree {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-family: Arial, sans-serif;
    min-width: max-content;
    padding: 12px;
    transform-origin: center center;
    will-change: transform;
}

.generation-level {
    display: flex;
    justify-content: center;
    margin: 20px 0;
    position: relative;
}

.person-box {
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 10px;
    margin: 0 10px;
    min-width: 150px;
    text-align: center;
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
}

.person-box:hover {
    background: #e9ecef;
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.person-box.male {
    border-color: #007bff;
    background: #e3f2fd;
}

.person-box.female {
    border-color: #e91e63;
    background: #fce4ec;
}

.person-name {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 4px;
    color: #333;
}

.person-dates {
    font-size: 12px;
    color: #666;
}

.expand-btn {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    cursor: pointer;
    display: none;
}

.person-box:hover .expand-btn {
    display: block;
}

.parents-container {
    display: flex;
    justify-content: space-around;
    width: 100%;
    margin-top: 20px;
}

.parent-branch {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.father-branch {
    margin-right: 10px;
}

.mother-branch {
    margin-left: 10px;
}

.empty-person-box {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    color: #6c757d;
    font-style: italic;
}

.connection-line {
    position: absolute;
    top: -10px;
    left: 50%;
    width: 2px;
    height: 20px;
    background: #ccc;
    transform: translateX(-50%);
}

@media (max-width: 768px) {
    .person-box {
        min-width: 120px;
        padding: 8px;
        margin: 0 5px;
    }

    .person-name {
        font-size: 12px;
    }

    .person-dates {
        font-size: 10px;
    }
}
</style>

<script>
function expandPerson(event, personId) {
    let wireId = null;

    if (window.Livewire?.all) {
        const pedigreeComponent = window.Livewire.all().find((component) => component.name === 'pedigree-chart');
        if (pedigreeComponent) {
            wireId = pedigreeComponent.id;
        }
    }

    if (!wireId) {
        const source = event && event.target ? event.target : null;
        const root = source ? source.closest('[wire\\:id]') : null;
        wireId = root ? root.getAttribute('wire:id') : null;
    }

    if (wireId && window.Livewire?.find) {
        const component = window.Livewire.find(wireId);
        if (component) {
            component.call('expandPerson', personId);
        }
    }
}

document.addEventListener('livewire:init', () => {
    Livewire.on('refreshChart', () => {
        setupPedigreeInteractions();
    });

    setupPedigreeInteractions();
});

function setupPedigreeInteractions() {
    const el = document.getElementById('pedigree-chart-display');
    if (!el || el.dataset.dragPanInit === '1') {
        return;
    }

    el.dataset.dragPanInit = '1';
    el.style.cursor = 'grab';

    let tree = el.querySelector('.pedigree-tree');
    let isDown = false;
    let startX = 0;
    let startY = 0;
    let offsetX = 0;
    let offsetY = 0;
    let scale = 1;

    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

    const syncTree = () => {
        const nextTree = el.querySelector('.pedigree-tree');
        if (!nextTree) {
            return;
        }

        tree = nextTree;
        tree.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(${scale})`;
    };

    const applyTransform = () => {
        syncTree();
        if (tree) {
            tree.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(${scale})`;
        }
    };

    syncTree();

    el.addEventListener('mousedown', (e) => {
        isDown = true;
        el.style.cursor = 'grabbing';
        startX = e.clientX;
        startY = e.clientY;
    });

    window.addEventListener('mouseup', () => {
        isDown = false;
        el.style.cursor = 'grab';
    });

    el.addEventListener('mouseleave', () => {
        isDown = false;
        el.style.cursor = 'grab';
    });

    el.addEventListener('mousemove', (e) => {
        if (!isDown) {
            return;
        }
        e.preventDefault();
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;
        startX = e.clientX;
        startY = e.clientY;
        offsetX += dx;
        offsetY += dy;
        applyTransform();
    });

    el.addEventListener('wheel', (e) => {
        if (!e.ctrlKey) {
            return;
        }

        e.preventDefault();
        const delta = e.deltaY > 0 ? -0.1 : 0.1;
        scale = clamp(Number((scale + delta).toFixed(2)), 0.5, 2.5);
        applyTransform();
    }, { passive: false });

    applyTransform();
}
    </script>
</div>
