@extends('layouts.app')

@section('title', 'THOR REBRAND TOOL - Workspace')

@section('content')
<style>
/* Emergency Stepper Styles for Local Dev */
.stepper {
  display: flex !important;
  flex-direction: row !important;
  justify-content: space-between !important;
  align-items: center !important;
  max-width: 800px;
  margin: 0 auto 48px;
  padding: 0 40px;
  position: relative;
  z-index: 5;
}
.stepper::before {
  content: '';
  position: absolute;
  top: 24px;
  left: 0;
  width: 100%;
  height: 2px;
  background: rgba(255,255,255,0.1);
  z-index: 1;
}
.step {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  flex: 1;
}
.step-circle {
  width: 48px;
  height: 48px;
  background: #16181f;
  border: 2px solid rgba(255,255,255,0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 1.125rem;
  color: #64748b;
  transition: all 0.3s ease;
}
.step.active .step-circle {
  border-color: #f97316;
  background: #f97316;
  color: #000;
  box-shadow: 0 0 20px rgba(249, 115, 22, 0.4);
}
.step.completed .step-circle {
  border-color: #10b981;
  background: #10b981;
  color: #000;
}
.step.clickable { cursor: pointer; }
.step.clickable:hover .step-circle {
  border-color: #f97316;
  transform: translateY(-2px);
}
.btn-nav {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    font-weight: 800;
    font-size: 0.9rem;
    letter-spacing: 0.02em;
    border-radius: 14px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}
.btn-nav-prev {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: var(--text-dim);
}
.btn-nav-prev:hover {
    background: rgba(255,255,255,0.08);
    color: var(--text-main);
    transform: translateX(-4px);
}
.step-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #64748b;
}
@keyframes pulse {
  0% { opacity: 0.6; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.05); }
  100% { opacity: 0.6; transform: scale(1); }
}
.step.active .step-label { color: #f97316; }
.step.completed .step-label { color: #10b981; }

/* Storage Card Styles */
.storage-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 16px 24px;
    margin-bottom: 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    backdrop-filter: blur(10px);
}
.storage-info {
    flex: 1;
}
.storage-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 8px;
}
.storage-title {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--text-dim);
    letter-spacing: 0.05em;
}
.storage-usage-text {
    font-size: 0.85rem;
    font-weight: 700;
}
.storage-bar-container {
    height: 8px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
    overflow: hidden;
}
.storage-bar-fill {
    height: 100%;
    width: 0%;
    transition: width 0.5s ease, background 0.3s ease;
}
.storage-status-normal { background: #10b981; }
.storage-status-warning { background: #f59e0b; }
.storage-status-full { background: #ef4444; }

.btn-clear-mem {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #ef4444;
    padding: 10px 16px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.btn-clear-mem:hover {
    background: #ef4444;
    color: #fff;
    transform: translateY(-2px);
}
</style>
<div class="stepper fade-in" style="max-width: 1000px;">
    <div class="step" id="step1">
        <div class="step-circle">1</div>
        <div class="step-label">Upload</div>
    </div>
    <div class="step" id="step2">
        <div class="step-circle">2</div>
        <div class="step-label">Analyze</div>
    </div>
    <div class="step" id="step3">
        <div class="step-circle">3</div>
        <div class="step-label">Configure</div>
    </div>
    <div class="step" id="step4">
        <div class="step-circle">4</div>
        <div class="step-label">Rebrand</div>
    </div>
    <div class="step" id="step5">
        <div class="step-circle">5</div>
        <div class="step-label">Re-edit</div>
    </div>
    <div class="step" id="step6">
        <div class="step-circle">6</div>
        <div class="step-label">Download</div>
    </div>
</div>

<div class="storage-card fade-in" style="max-width: 1000px; margin: 0 auto 32px;" id="storageControlCard">
    <div class="storage-info">
        <div class="storage-header">
            <span class="storage-title">Engine Storage Usage</span>
            <span class="storage-usage-text" id="storageUsageLabel">0 GB / 20 GB</span>
        </div>
        <div class="storage-bar-container">
            <div id="storageBarFill" class="storage-bar-fill storage-status-normal"></div>
        </div>
    </div>
    <div style="text-align: right;">
        <button type="button" class="btn-clear-mem" id="clearMemoryBtn">
            <span style="font-size: 1.1rem; vertical-align: middle; margin-right: 4px;">🧹</span> 
            CLEAR MEMORY
        </button>
    </div>
</div>

<div class="studio-card fade-in" id="mainContainer" style="max-width: 1000px; margin: 0 auto;">
    <!-- STEP 1: UPLOAD -->
    <section id="idleSection">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div class="section-label" style="margin: 0;">
                <h2 class="section-title" style="margin: 0;">Step 1: Upload Your Files</h2>
            </div>
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 8px 16px; border-radius: 99px; font-weight: 800; font-size: 0.85rem; transition: all 0.3s ease;">
                <input type="checkbox" id="globalAutoProcess" style="width: 16px; height: 16px; accent-color: var(--success); cursor: pointer;">
                AUTO-PILOT
            </label>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px; align-items: stretch;">
            <!-- LOCAL DROPZONE -->
            <div class="dropzone" id="dropzone" style="cursor: pointer; height: 100%; display: flex; flex-direction: column; justify-content: center; margin: 0;">
                <span class="drop-icon" style="pointer-events: none;">📁</span>
                <p class="drop-text" style="pointer-events: none;">Drag files here or click to browse</p>
                <p class="drop-subtext" style="pointer-events: none; margin-top: 8px;">Supports .brushset, .brush, .procreate, .swatches, .usdz, and .zip</p>
            </div>
            <input id="fileInput" type="file" multiple hidden accept=".brushset,.brush,.procreate,.swatches,.usdz,.zip" onclick="event.stopPropagation()">

            <!-- LINK UPLOAD -->
            <div class="studio-card" style="margin: 0; padding: 32px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: center;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                    <div style="padding: 10px; background: rgba(249, 115, 22, 0.1); border-radius: 12px; font-size: 1.5rem;">🔗</div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800;">Cloud Import</h3>
                        <p class="drop-subtext" style="font-size: 0.75rem; color: var(--text-muted);">Paste Google Drive or direct links</p>
                    </div>
                </div>
                
                <div class="control-group" style="margin-bottom: 20px;">
                    <input type="text" id="linkUploadInput" class="text-input" placeholder="https://drive.google.com/..." style="background: rgba(0,0,0,0.3); height: 52px; font-size: 0.85rem;">
                </div>
                
                <button type="button" id="linkUploadBtn" class="btn btn-secondary" style="height: 52px; width: 100%; font-weight: 800; border-color: rgba(249, 115, 22, 0.3); color: var(--primary);">
                    Download & Import
                </button>
                
                <p class="drop-subtext" style="font-size: 0.65rem; text-align: center; margin-top: 16px; opacity: 0.6;">
                    System will securely fetch the file to the studio node.
                </p>
            </div>
        </div>

        <div id="selectionPanel" style="display: none; margin-top: 32px;">
            <p class="control-label" id="stagedCountLabel">0 files ready</p>
            <div id="fileGrid" class="file-grid"></div>
            <div style="margin-top: 32px; display: flex; justify-content: flex-end;">
                <button type="button" id="launchBtn" class="btn btn-primary" disabled>Start Upload</button>
            </div>
        </div>
    </section>

    <!-- PROCESSING PROGRESS -->
    <section id="progressSection" class="status-card" style="display: none; padding: 48px 32px; text-align: center;">
        <div style="margin-bottom: 24px;">
            <span class="badge" id="stageBadge" style="background: rgba(249, 115, 22, 0.1); color: var(--primary); border-color: rgba(249, 115, 22, 0.2); font-weight: 800; letter-spacing: 0.05em;">STAGE 1</span>
        </div>
        
        <div id="progressPercent" style="font-size: 5rem; font-weight: 900; color: var(--primary); margin-bottom: 24px; line-height: 1; letter-spacing: -2px;">0%</div>
        
        <div class="progress-track" style="height: 10px; background: rgba(255,255,255,0.05); border-radius: 5px; margin: 0 auto 32px; max-width: 600px; overflow: hidden;">
            <div id="progressFill" class="progress-bar" style="width: 0%; height: 100%; border-radius: 5px; transition: width 0.3s ease;"></div>
        </div>

        <h2 id="stageTitle" class="hero-title" style="font-size: 1.85rem; margin-bottom: 8px;">Syncing to Engine...</h2>
        <p id="stageMessage" class="drop-subtext" style="margin-bottom: 48px; font-size: 0.95rem;">Preparing data transmission...</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; max-width: 750px; margin: 0 auto;">
            <div class="stat-box" style="background: rgba(255,255,255,0.02); padding: 20px 16px; border-radius: 16px; border: 1px solid var(--border-color); backdrop-filter: blur(10px);">
                <p class="control-label" style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Upload Speed</p>
                <p id="statSpeed" style="font-weight: 800; font-size: 1.1rem; color: var(--text-main);">Calculating...</p>
            </div>
            <div class="stat-box" style="background: rgba(255,255,255,0.02); padding: 20px 16px; border-radius: 16px; border: 1px solid var(--border-color); backdrop-filter: blur(10px);">
                <p class="control-label" style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Transferred</p>
                <p id="statTransferred" style="font-weight: 800; font-size: 1.1rem; color: var(--text-main);">0 B / 0 B</p>
            </div>
            <div class="stat-box" style="background: rgba(255,255,255,0.02); padding: 20px 16px; border-radius: 16px; border: 1px solid var(--border-color); backdrop-filter: blur(10px);">
                <p class="control-label" style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">ETA</p>
                <p id="statEta" style="font-weight: 800; font-size: 1.1rem; color: var(--text-main);">Calculating...</p>
            </div>
            <div class="stat-box" style="background: rgba(255,255,255,0.02); padding: 20px 16px; border-radius: 16px; border: 1px solid var(--border-color); backdrop-filter: blur(10px);">
                <p class="control-label" style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Files</p>
                <p id="statFiles" style="font-weight: 800; font-size: 1.1rem; color: var(--text-main);">0 / 0</p>
            </div>
        </div>
    </section>

    <!-- STEP 2/3: SCAN RESULTS & CONFIGURATION -->
    <section id="scannedSection" style="display: none; width: 100%; max-width: 1200px; margin: 0 auto;">
        
        <!-- IDENTITY INSIGHTS HEADER -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; padding: 0 10px;">
            <div>
                <p class="control-label" style="font-size: 0.75rem; letter-spacing: 0.1em; color: var(--text-dim); margin-bottom: 8px;">STEP 3: IDENTITY INSIGHTS</p>
                <h2 class="hero-title" style="font-size: 2.25rem; margin: 0;">Scan intelligence is ready for review</h2>
            </div>
            <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: var(--success); border-color: rgba(16, 185, 129, 0.2); font-weight: 800; padding: 8px 16px; border-radius: 20px;">SCAN COMPLETE</span>
        </div>

        <!-- STATS GRID -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; margin-bottom: 40px;">
            <div class="studio-card" style="margin: 0; padding: 24px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color);">
                <p class="control-label" style="font-size: 0.7rem; margin-bottom: 12px; opacity: 0.6;">DETECTED AUTHORS</p>
                <div id="detectedAuthorsList" style="display: flex; gap: 8px; flex-wrap: wrap; min-height: 40px; align-content: flex-start;">
                    <!-- Authors injected here -->
                </div>
            </div>
            <div class="studio-card" style="margin: 0; padding: 24px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color);">
                <p class="control-label" style="font-size: 0.7rem; margin-bottom: 12px; opacity: 0.6;">TOTAL ASSETS FOUND</p>
                <div id="totalAssetsCount" style="font-size: 2.5rem; font-weight: 900; color: var(--text-main);">0</div>
            </div>
            <div class="studio-card" style="margin: 0; padding: 24px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color);">
                <p class="control-label" style="font-size: 0.7rem; margin-bottom: 12px; opacity: 0.6;">UPLOADED FILES</p>
                <div id="uploadedFilesCount" style="font-size: 2.5rem; font-weight: 900; color: var(--text-main);">0</div>
            </div>
            <div class="studio-card" style="margin: 0; padding: 24px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color);">
                <p class="control-label" style="font-size: 0.7rem; margin-bottom: 12px; opacity: 0.6;">INTEGRITY STATUS</p>
                <div style="font-size: 1.5rem; font-weight: 900; color: var(--success); display: flex; align-items: center; gap: 8px;">
                    Verified <span style="font-size: 1rem; opacity: 0.8;">📦</span>
                </div>
            </div>
        </div>

        <!-- REBRANDING CONFIGURATION FORM -->
        <div class="studio-card" style="margin: 0 0 40px; padding: 32px; background: rgba(249, 115, 22, 0.03); border: 1px solid rgba(249, 115, 22, 0.2); position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <p class="control-label" style="font-size: 0.7rem; margin-bottom: 8px; opacity: 0.6;">REBRANDING</p>
                    <h2 style="font-size: 2rem; font-weight: 900;">Identity Configuration</h2>
                </div>
                <span class="badge" style="background: rgba(249, 115, 22, 0.1); color: var(--primary); border-color: rgba(249, 115, 22, 0.2); font-weight: 800;">READY TO APPLY</span>
            </div>

            <!-- Configuration Meta Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 32px;">
                <div class="stat-box" style="background: rgba(0,0,0,0.2); padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <p class="control-label" style="font-size: 0.6rem; opacity: 0.5; margin-bottom: 8px;">TOTAL .BRUSHSET</p>
                    <p id="countBrushset" style="font-size: 1.5rem; font-weight: 800;">0</p>
                </div>
                <div class="stat-box" style="background: rgba(0,0,0,0.2); padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <p class="control-label" style="font-size: 0.6rem; opacity: 0.5; margin-bottom: 8px;">TOTAL .BRUSH</p>
                    <p id="countBrush" style="font-size: 1.5rem; font-weight: 800;">0</p>
                </div>
                <div class="stat-box" style="background: rgba(0,0,0,0.2); padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <p class="control-label" style="font-size: 0.6rem; opacity: 0.5; margin-bottom: 8px;">TOTAL .PROCREATE</p>
                    <p id="countProcreate" style="font-size: 1.5rem; font-weight: 800;">0</p>
                </div>
                <div class="stat-box" style="background: rgba(249, 115, 22, 0.05); padding: 16px; border-radius: 12px; border: 1px solid rgba(249, 115, 22, 0.2);">
                    <p class="control-label" style="font-size: 0.6rem; color: var(--primary); opacity: 0.8; margin-bottom: 8px;">DETECTED MADE BY</p>
                    <p id="primaryDetectedAuthor" style="font-size: 1.25rem; font-weight: 900; color: var(--text-main);">None</p>
                    <p class="drop-subtext" style="font-size: 0.65rem; margin-top: 4px;">Detected directly from the Procreate brush archive</p>
                </div>
                <div class="stat-box" style="background: rgba(239, 68, 68, 0.05); padding: 16px; border-radius: 12px; border: 1px solid rgba(239, 68, 68, 0.2);">
                    <p class="control-label" style="font-size: 0.6rem; color: #ef4444; opacity: 0.8; margin-bottom: 8px;">COPYRIGHT RISK</p>
                    <p style="font-size: 1.25rem; font-weight: 900; color: #ef4444;">Review</p>
                    <p id="copyrightAssetCount" class="drop-subtext" style="font-size: 0.65rem; margin-top: 4px; color: #ef4444; opacity: 0.8;">0 embedded author tags found</p>
                </div>
            </div>

            <!-- Configuration Inputs -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 32px;">
                <div class="control-group">
                    <label class="control-label" style="font-size: 0.65rem; letter-spacing: 0.05em;">NEW AUTHOR NAME</label>
                    <input id="authorNameIn" class="text-input" type="text" placeholder="e.g. Creative Studio" style="background: rgba(0,0,0,0.3); height: 52px; font-weight: 700;">
                </div>
                <div class="control-group">
                    <label class="control-label" style="font-size: 0.65rem; letter-spacing: 0.05em;">STORE NAME (FILENAME)</label>
                    <input id="storeNameIn" class="text-input" type="text" placeholder="e.g. Master Texture Pack" style="background: rgba(0,0,0,0.3); height: 52px; font-weight: 700;">
                </div>
                <div class="control-group">
                    <label class="control-label" style="font-size: 0.65rem; letter-spacing: 0.05em;">SIGNATURE (PNG) <span style="opacity: 0.5;">- required</span></label>
                    <div style="display: flex; gap: 8px;">
                        <input id="sigFileName" class="text-input" type="text" readonly placeholder="No file selected" style="background: rgba(0,0,0,0.3); height: 52px; flex: 1; font-size: 0.8rem; opacity: 0.7;">
                        <button type="button" onclick="document.getElementById('sigInput').click()" class="btn btn-secondary" style="height: 52px; width: 60px; padding: 0;">📁</button>
                    </div>
                    <input id="sigInput" type="file" hidden accept=".png">
                    <p id="clearSig" class="drop-subtext" style="font-size: 0.65rem; color: var(--primary); cursor: pointer; text-transform: uppercase; margin-top: 8px; display: none;">Clear Signature</p>
                </div>
                <div class="control-group">
                    <label class="control-label" style="font-size: 0.65rem; letter-spacing: 0.05em;">AUTHOR PIC (PNG) <span style="opacity: 0.5;">- required</span></label>
                    <div style="display: flex; gap: 8px;">
                        <input id="picFileName" class="text-input" type="text" readonly placeholder="No file selected" style="background: rgba(0,0,0,0.3); height: 52px; flex: 1; font-size: 0.8rem; opacity: 0.7;">
                        <button type="button" onclick="document.getElementById('picInput').click()" class="btn btn-secondary" style="height: 52px; width: 60px; padding: 0;">📁</button>
                    </div>
                    <input id="picInput" type="file" hidden accept=".png">
                    <p id="clearPic" class="drop-subtext" style="font-size: 0.65rem; color: var(--primary); cursor: pointer; text-transform: uppercase; margin-top: 8px; display: none;">Clear Picture</p>
                </div>
                <div class="control-group" style="grid-column: 1 / -1; margin-top: 16px;">
                    <label class="control-label" style="display: flex; align-items: center; gap: 12px; cursor: pointer; color: var(--text-main); font-size: 0.85rem; font-weight: 800;">
                        <input type="checkbox" id="autoProcessCheckbox" style="width: 20px; height: 20px; accent-color: var(--primary);">
                        Enable Auto-Process (Automatically apply changes & repackage after saving config)
                    </label>
                </div>
            </div>

            <p class="drop-subtext" style="font-size: 0.75rem; margin-bottom: 32px; opacity: 0.6;">Saved auto-process config is tied to this signed-in account.</p>

            <div class="config-action-row" style="display: flex; gap: 16px; margin-top: 40px; padding-top: 32px; border-top: 1px solid rgba(255,255,255,0.05); justify-content: flex-end;">
                <button type="button" id="saveConfigBtn" class="btn btn-secondary config-action-btn config-action-btn-secondary">Save Config</button>
                <button type="button" id="rebrandBtn" class="btn btn-primary config-action-btn config-action-btn-primary">Apply Changes &amp; Repackage</button>
            </div>
        </div>

        <!-- ASSET CLASSIFICATION PREVIEW -->
        <div class="studio-card" style="margin: 0 0 40px; padding: 32px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color);">
            <p class="control-label" style="font-size: 0.7rem; margin-bottom: 8px; opacity: 0.6;">ASSET CLASSIFICATION</p>
            <h3 style="font-size: 2rem; font-weight: 800; margin-bottom: 32px;">Preview detected files before rebrand</h3>
            
            <div id="assetPreviewGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; max-height: 500px; overflow-y: auto; padding-right: 10px;">
                <!-- Asset cards injected here -->
            </div>
        </div>

    </section>

    <!-- STEP 5: RE-EDIT -->
    <section id="reeditSection" style="display: none; width: 100%; max-width: 1200px; margin: 0 auto;">
        <!-- PER FILE DETECTION LIST -->
        <div class="studio-card" style="margin: 0 0 40px; padding: 32px; background: rgba(249, 115, 22, 0.02); border: 1px solid rgba(249, 115, 22, 0.1);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
                <div>
                    <p class="control-label" style="font-size: 0.7rem; margin-bottom: 8px; opacity: 0.6;">PER FILE DETECTION</p>
                    <h3 style="font-size: 1.75rem; font-weight: 800;">Each extracted source keeps its own made-by scan</h3>
                </div>
                <p id="sourceCoverageInfo" class="drop-subtext" style="font-size: 0.85rem; font-weight: 600;">0/0 uploaded files contain embedded author tags</p>
            </div>
            
            <div id="sourceFilesList" style="display: flex; flex-direction: column; gap: 16px;">
                <!-- Source file entries injected here -->
            </div>
        </div>

        <div style="display: flex; gap: 16px; justify-content: flex-end; margin-top: 40px; padding-top: 32px; border-top: 1px solid rgba(255,255,255,0.05);">
            <button type="button" class="btn btn-secondary" onclick="state.status='scanned'; updateView();">Back to Configuration</button>
            <button type="button" class="btn btn-primary" onclick="state.status='completed'; updateView();">Go to Download</button>
        </div>
    </section>

    <!-- STEP 6: DOWNLOAD -->
    <section id="completedSection" class="status-card" style="display: none;">
        <div class="logo-icon" style="margin: 0 auto 24px; background: var(--success);">✓</div>
        <h2 class="section-title">Step 6: Download Complete</h2>
        <p class="drop-subtext">All files have been successfully rebranded with your info.</p>
        <div id="outputContainer" style="margin: 32px 0;"></div>
        <div style="display: flex; gap: 16px; justify-content: center; margin-top: 40px; padding-top: 32px; border-top: 1px solid rgba(255,255,255,0.05);">
            <a id="downloadAllBtn" href="#" class="btn btn-primary">Download Brand-New Files</a>
            <button type="button" class="btn btn-secondary" id="processMoreBtn">Start Over</button>
        </div>
    </section>

    <!-- ERROR ZONE -->
    <section id="errorSection" class="status-card" style="display: none;">
        <div class="logo-icon" style="margin: 0 auto 24px; background: var(--error);">!</div>
        <h2 class="section-title" style="color: var(--error)">Error Detected</h2>
        <p id="errorMessage" class="drop-subtext"></p>
        <button type="button" class="btn btn-secondary" id="retryBtn" style="margin-top: 32px;">Try Again</button>
    </section>
</div>

<!-- Maintenance Tools -->
<div class="studio-card fade-in" style="max-width: 1000px; margin: 24px auto 0; border: 1px solid rgba(239, 68, 68, 0.15); background: rgba(239, 68, 68, 0.02);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="padding: 12px; background: rgba(239, 68, 68, 0.1); border-radius: 12px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center;">
                🧹
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-main);">Storage Management</h3>
                <p class="drop-subtext" style="margin-top: 4px; margin-bottom: 0; font-size: 0.85rem;">Clear temporary files and maintain system performance.</p>
            </div>
        </div>
        <button type="button" id="cleanupBtn" class="btn btn-secondary" style="border-color: rgba(239, 68, 68, 0.3); color: #ef4444; font-weight: 800;">Clear Memory</button>
    </div>
    
    <div id="cleanupProgressWrap" style="display: none; margin-top: 24px; padding-top: 24px; border-top: 1px solid rgba(239, 68, 68, 0.1);">
        <div class="progress-track" style="margin: 0 0 12px 0; background: rgba(0,0,0,0.3);">
            <div id="cleanupProgressFill" class="progress-bar" style="width: 0%; background: #ef4444;"></div>
        </div>
        <p id="cleanupFeedback" class="drop-subtext" style="font-size: 0.8rem; color: #ef4444; font-weight: 600; m-0"></p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const CONFIGURED_API_BASE = @json(config('services.backend.url'));
    const normalizeBase = (value) => value ? value.replace(/\/$/, '') : '';
    const shouldRetryWithFallback = (status) => [404, 500, 502, 503, 504].includes(status);

    // Point to local Laravel engine by default
    const LARAVEL_ENGINE_BASE = '/studio-engine';
    let activeApiBase = LARAVEL_ENGINE_BASE;

    function getApiBases() {
        const bases = [LARAVEL_ENGINE_BASE];
        return Array.from(new Set(bases.filter(Boolean)));
    }

    function getResolvedApiBase() {
        return activeApiBase || getApiBases()[0] || '';
    }

    async function apiFetch(path, init = {}) {
        let lastResponse = null;
        let lastError = null;

        for (const base of getApiBases()) {
            try {
                const response = await fetch(`${base}${path}`, {
                    ...init,
                    headers: {
                        ...init.headers,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });
                if (response.ok) {
                    activeApiBase = base;
                    return response;
                }

                lastResponse = response;
                if (!shouldRetryWithFallback(response.status)) {
                    activeApiBase = base;
                    return response;
                }
            } catch (error) {
                lastError = error;
            }
        }

        if (lastResponse) return lastResponse;
        throw lastError || new Error('Request failed');
    }

    async function readErrorMessage(response, fallback) {
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const data = await response.json().catch(() => null);
            if (typeof data?.detail === 'string' && data.detail.trim()) {
                return data.detail;
            }
        }

        const text = (await response.text().catch(() => '')).trim();
        return text ? `${fallback} (${response.status}): ${text.slice(0, 180)}` : `${fallback} (${response.status})`;
    }

    function buildUploadFormData(files) {
        const formData = new FormData();
        files.forEach(file => formData.append('files[]', file, file.name));
        return formData;
    }

    const CHUNK_SIZE = 10 * 1024 * 1024; // 10MB Chunks

    async function uploadWithChunks(files) {
        const jobId = typeof crypto.randomUUID === 'function' ? crypto.randomUUID() : Math.random().toString(36).substring(2);
        const totalSize = files.reduce((acc, f) => acc + f.size, 0);
        let uploadedBytes = 0;
        const startTime = Date.now();

        for (const file of files) {
            const totalChunks = Math.ceil(file.size / CHUNK_SIZE) || 1;
            for (let i = 0; i < totalChunks; i++) {
                const start = i * CHUNK_SIZE;
                const end = Math.min(file.size, start + CHUNK_SIZE);
                const chunk = file.slice(start, end);

                const formData = new FormData();
                formData.append('job_id', jobId);
                formData.append('file_name', file.name);
                formData.append('chunk_index', i);
                formData.append('total_chunks', totalChunks);
                formData.append('chunk', chunk);

                const response = await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', `${activeApiBase}/upload-chunk`);
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
                    
                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            const currentUploaded = uploadedBytes + e.loaded;
                            const percent = Math.min(99, Math.round((currentUploaded / totalSize) * 100));
                            
                            if (elements.progressPercent) elements.progressPercent.innerText = percent + "%";
                            if (elements.progressFill) elements.progressFill.style.width = percent + "%";
                            
                            const elapsed = (Date.now() - startTime) / 1000;
                            const speed = currentUploaded / (elapsed || 0.1);
                            const remaining = totalSize - currentUploaded;
                            const eta = speed > 0 ? Math.ceil(remaining / speed) : 0;
                            
                            if (elements.statSpeed) elements.statSpeed.innerText = formatFileSize(speed) + "/s";
                            if (elements.statTransferred) elements.statTransferred.innerText = `${formatFileSize(currentUploaded)} / ${formatFileSize(totalSize)}`;
                            if (elements.statEta) {
                                if (eta > 0) {
                                    const mins = Math.floor(eta / 60);
                                    const secs = eta % 60;
                                    elements.statEta.innerText = mins > 0 ? `${mins}m ${secs}s` : `${secs}s`;
                                } else {
                                    elements.statEta.innerText = "Finishing...";
                                }
                            }
                        }
                    };

                    xhr.onload = () => resolve(new Response(xhr.responseText, { status: xhr.status }));
                    xhr.onerror = () => reject(new Error('Chunk upload failed'));
                    xhr.send(formData);
                });

                if (!response.ok) throw new Error('Failed to upload chunk');
                uploadedBytes += (end - start);
            }
        }

        // Finalize
        const finalizeResp = await apiFetch('/finalize-upload', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_id: jobId })
        });

        if (!finalizeResp.ok) throw new Error('Finalize failed');
        return finalizeResp;
    }
    
    let state = {
        files: [],
        jobId: null,
        status: 'idle',
        manifest: null,
        outputs: [],
        bundle: null,
        storeName: '',
        finalZipName: ''
    };

    const elements = {
        fileInput: document.getElementById('fileInput'),
        dropzone: document.getElementById('dropzone'),
        fileGrid: document.getElementById('fileGrid'),
        selectionPanel: document.getElementById('selectionPanel'),
        stagedCountLabel: document.getElementById('stagedCountLabel'),
        launchBtn: document.getElementById('launchBtn'),
        
        idleSection: document.getElementById('idleSection'),
        progressSection: document.getElementById('progressSection'),
        scannedSection: document.getElementById('scannedSection'),
        reeditSection: document.getElementById('reeditSection'),
        completedSection: document.getElementById('completedSection'),
        errorSection: document.getElementById('errorSection'),
        
        progressPercent: document.getElementById('progressPercent'),
        progressFill: document.getElementById('progressFill'),
        stageTitle: document.getElementById('stageTitle'),
        stageMessage: document.getElementById('stageMessage'),
        stageBadge: document.getElementById('stageBadge'),
        statSpeed: document.getElementById('statSpeed'),
        statTransferred: document.getElementById('statTransferred'),
        statEta: document.getElementById('statEta'),
        statFiles: document.getElementById('statFiles'),
        
        assetSummary: document.getElementById('assetSummary'),
        errorMessage: document.getElementById('errorMessage'),
        retryBtn: document.getElementById('retryBtn'),
        processMoreBtn: document.getElementById('processMoreBtn'),
        
        cleanupBtn: document.getElementById('cleanupBtn'),
        cleanupFeedback: document.getElementById('cleanupFeedback'),
        cleanupProgressWrap: document.getElementById('cleanupProgressWrap'),
        cleanupProgressFill: document.getElementById('cleanupProgressFill'),
        
        backToUploadBtn: document.getElementById('backToUploadBtn'),
        backToScannedBtn: document.getElementById('backToScannedBtn')
    };

    function formatFileSize(bytes) {
        if (!bytes || bytes <= 0) return "0 B";
        const units = ["B", "KB", "MB", "GB"];
        let size = bytes;
        let unitIndex = 0;
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex += 1;
        }
        return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
    }

    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        switch(ext) {
            case 'brush':
            case 'brushset': return '🎨';
            case 'procreate': return '🖼️';
            case 'swatches': return '🌈';
            case 'usdz': return '📦';
            default: return '📄';
        }
    }

    function updateView() {
        const s1 = document.getElementById('step1');
        const s2 = document.getElementById('step2');
        const s3 = document.getElementById('step3');
        const s4 = document.getElementById('step4');
        const s5 = document.getElementById('step5');
        const s6 = document.getElementById('step6');

        // Reset and check clickability
        [s1, s2, s3, s4, s5, s6].forEach(s => {
            s.classList.remove('active', 'completed', 'clickable');
            s.onclick = null;
        });

        // Helper to make steps clickable
        const makeClickable = (el, status) => {
            el.classList.add('clickable');
            el.onclick = () => {
                state.status = status;
                updateView();
            };
        };

        if (state.status !== 'idle' && state.status !== 'error') {
            makeClickable(s1, 'idle');
        }
        if (state.manifest) {
            makeClickable(s2, 'scanned');
            makeClickable(s3, 'scanned');
        }
        if (state.outputs && state.outputs.length > 0) {
            makeClickable(s4, 'completed');
            makeClickable(s5, 'reedit'); // Step 5 Re-edit goes to new reedit state
            makeClickable(s6, 'completed');
        }

        // Hide all major sections
        [elements.idleSection, elements.progressSection, elements.scannedSection, elements.reeditSection, elements.completedSection, elements.errorSection]
            .forEach(s => { if(s) s.style.display = 'none'; });

        if (state.status === 'idle') {
            elements.idleSection.style.display = 'block';
            s1.classList.add('active');
            renderFiles();
        } else if (['uploading', 'scanning', 'processing'].includes(state.status)) {
            elements.progressSection.style.display = 'block';
            
            if (state.status === 'uploading') {
                s1.classList.add('active');
                elements.stageBadge.innerText = "STAGE 1";
                elements.stageTitle.innerText = "Syncing to Engine...";
                elements.stageMessage.innerText = "Encrypting and preparing your design workspace.";
            } else if (state.status === 'scanning') {
                s1.classList.add('completed');
                s2.classList.add('active');
                elements.stageBadge.innerText = "STAGE 2";
                elements.stageTitle.innerText = "Analyzing Plist...";
                elements.stageMessage.innerText = "Deep scanning binary plists and asset metadata.";
            } else if (state.status === 'processing') {
                [s1, s2, s3].forEach(s => s.classList.add('completed'));
                s4.classList.add('active');
                elements.stageBadge.innerText = "STAGE 4";
                elements.stageTitle.innerText = "Applying Rebrand...";
                elements.stageMessage.innerText = "Injecting new identity and repackaging files.";
            }
        } else if (state.status === 'uploaded') {
            // Step 2 starts
            s1.classList.add('completed');
            s2.classList.add('active');
            elements.stageBadge.innerText = "STAGE 2";
            handleScan();
        } else if (state.status === 'scanned') {
            elements.scannedSection.style.display = 'block';
            [s1, s2].forEach(s => s.classList.add('completed'));
            s3.classList.add('active');
            renderScanned();
        } else if (state.status === 'completed') {
            elements.completedSection.style.display = 'block';
            [s1, s2, s3, s4, s5].forEach(s => s.classList.add('completed'));
            s6.classList.add('active');
            elements.stageBadge.innerText = "STAGE 6";
            renderOutputs();
        } else if (state.status === 'reedit') {
            if (elements.reeditSection) elements.reeditSection.style.display = 'block';
            [s1, s2, s3, s4].forEach(s => s.classList.add('completed'));
            s5.classList.add('active');
            elements.stageBadge.innerText = "STAGE 5";
            renderReedit();
        } else if (state.status === 'error') {
            elements.errorSection.style.display = 'block';
        }
    }

    function renderFiles() {
        if (state.files.length === 0) {
            elements.selectionPanel.style.display = 'none';
            elements.launchBtn.disabled = true;
            return;
        }

        elements.selectionPanel.style.display = 'block';
        elements.launchBtn.disabled = false;
        elements.stagedCountLabel.innerText = `${state.files.length} file(s) ready for Step 1`;

        elements.fileGrid.innerHTML = '';
        state.files.forEach((file, index) => {
            const div = document.createElement('div');
            div.className = 'file-item';
            div.innerHTML = `
                <div class="file-icon">${getFileIcon(file.name)}</div>
                <div class="file-info">
                    <div class="file-name" title="${file.name}">${file.name}</div>
                    <div class="file-meta">${formatFileSize(file.size)}</div>
                </div>
                <button type="button" class="remove-btn" title="Remove">&times;</button>
            `;
            div.querySelector('.remove-btn').onclick = () => {
                state.files.splice(index, 1);
                renderFiles();
            };
            elements.fileGrid.appendChild(div);
        });
    }

    function renderScanned() {
        if (!state.manifest) return;
        const m = state.manifest;
        const authors = Array.isArray(m.detected_authors) ? m.detected_authors : [];
        const assets = Array.isArray(m.assets) ? m.assets : [];
        const sources = Array.isArray(m.source_files) ? m.source_files : []; 
        
        // Update Stats
        document.getElementById('detectedAuthorsList').innerHTML = authors.map(a => `
            <span class="badge" style="margin: 0; padding: 6px 12px; background: rgba(249, 115, 22, 0.1); border-color: rgba(249, 115, 22, 0.2); color: var(--primary); font-size: 0.75rem; font-weight: 700;">${a}</span>
        `).join('') || '<span class="drop-subtext">None detected</span>';
        
        document.getElementById('totalAssetsCount').innerText = assets.length;
        document.getElementById('uploadedFilesCount').innerText = sources.length;
        
        // Count specific extensions in the assets AND source files
        let countBrushset = 0, countBrush = 0, countProcreate = 0;
        
        // Count in assets (for nested/extracted tools)
        assets.forEach(a => {
            const path = a.rel_path || a.file || "";
            const ext = path.split('.').pop().toLowerCase();
            if (ext === 'brushset') countBrushset++;
            if (ext === 'brush') countBrush++;
            if (ext === 'procreate') countProcreate++;
        });

        // Count in source files (for original uploads)
        sources.forEach(s => {
            const ext = s.name.split('.').pop().toLowerCase();
            if (ext === 'brushset') countBrushset++;
            if (ext === 'brush') countBrush++;
            if (ext === 'procreate') countProcreate++;
        });
        
        document.getElementById('countBrushset').innerText = countBrushset;
        document.getElementById('countBrush').innerText = countBrush;
        document.getElementById('countProcreate').innerText = countProcreate;

        const mainAuthor = (authors[0] || "None");
        document.getElementById('primaryDetectedAuthor').innerText = (mainAuthor.startsWith('$')) ? "None" : mainAuthor;
        
        // Asset Preview Grid
        const previewGrid = document.getElementById('assetPreviewGrid');
        previewGrid.innerHTML = assets.slice(0, 50).map(asset => {
            const path = asset.rel_path || "";
            const isImage = /\.(png|jpg|jpeg|gif|webp)$/i.test(path);
            const resolvedApiBase = getResolvedApiBase();
            const previewUrl = isImage ? `${resolvedApiBase}/jobs/${state.jobId}/assets/preview?path=${encodeURIComponent(path)}` : null;
            const downloadUrl = `${resolvedApiBase}/jobs/${state.jobId}/assets/${encodeURIComponent(path)}`;
            
            return `
                <div class="studio-card" style="margin: 0; padding: 12px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); overflow: hidden;">
                    <div style="aspect-ratio: 4/3; background: #111; background-image: linear-gradient(45deg, #181818 25%, transparent 25%), linear-gradient(-45deg, #181818 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #181818 75%), linear-gradient(-45deg, transparent 75%, #181818 75%); background-size: 20px 20px; background-position: 0 0, 0 10px, 10px -10px, -10px 0px; border-radius: 8px; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                        ${previewUrl ? `<img src="${previewUrl}" style="max-width: 100%; max-height: 100%; object-fit: contain;">` : `<span style="font-size: 2rem;">${getFileIcon(path)}</span>`}
                        <a href="${downloadUrl}" target="_blank" class="badge" style="position: absolute; top: 8px; left: 8px; font-size: 0.6rem; padding: 2px 6px; background: rgba(0,0,0,0.8); border: none; cursor: pointer; text-decoration: none;">DOWNLOAD</a>
                        <span style="position: absolute; top: 8px; right: 8px; font-size: 0.65rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">${asset.category || 'asset'}</span>
                    </div>
                    <div style="padding: 0 4px;">
                        <p style="font-weight: 700; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px;" title="${asset.name || path.split('/').pop()}">${asset.name || path.split('/').pop()}</p>
                        <p style="font-size: 0.7rem; color: var(--text-dim); font-weight: 600;">${formatFileSize(asset.size)}</p>
                        <div style="border-top: 1px solid rgba(255,255,255,0.05); margin-top: 8px; padding-top: 8px;">
                            <p style="font-size: 0.6rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700; opacity: 0.6; margin-bottom: 2px;">Source File</p>
                            <p style="font-size: 0.65rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; opacity: 0.8;">${asset.source_name || "Extracted Source"}</p>
                        </div>
                    </div>
                </div>
            `;
        }).join('') || '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-dim);">No previewable assets found in the scan.</div>';

        // Source List
        const sourceList = document.getElementById('sourceFilesList');
        let authorTaggedCount = 0;
        sourceList.innerHTML = sources.map(src => {
            const authorTags = src.author_tags || [];
            const hasAuthor = authorTags.length > 0;
            if (hasAuthor) authorTaggedCount++;
            
            return `
                <div style="display: flex; flex-direction: column; gap: 12px; padding: 20px 24px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <span class="badge" style="font-size: 0.6rem; padding: 4px 10px; opacity: 0.8;">${(src.name || "").split('.').pop().toUpperCase()}</span>
                            <div>
                                <p style="font-weight: 800; font-size: 1rem; margin-bottom: 2px;">${src.name}</p>
                                <p style="font-size: 0.75rem; color: var(--text-dim); font-weight: 600;">${formatFileSize(src.size)} • ${src.asset_count || 0} assets</p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            ${authorTags.map(t => `<span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-dim); border: 1px solid var(--border-color); font-size: 0.65rem;">${t}</span>`).join('') || '<span class="badge" style="background: rgba(239, 68, 68, 0.05); color: #ef4444; border-color: rgba(239, 68, 68, 0.1); font-size: 0.65rem;">No Tags</span>'}
                        </div>
                    </div>
                    <div style="background: rgba(0,0,0,0.2); padding: 10px 16px; border-radius: 8px; border-left: 3px solid var(--primary);">
                        <p style="font-size: 0.6rem; text-transform: uppercase; font-weight: 800; color: var(--text-dim); margin-bottom: 4px; letter-spacing: 0.05em;">Rebrand Outcome Preview</p>
                        <div id="smartPreview_${sources.indexOf(src)}">
                            <span style="opacity: 0.5; font-size: 0.8rem;">Awaiting branding...</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        // Smart AI Preview Logic
        const storeNameIn = document.getElementById('storeNameIn');
        const updateSmartPreview = () => {
            if (!storeNameIn) return;
            const storeName = storeNameIn.value.trim();
            sources.forEach((src, idx) => {
                const previewEl = document.getElementById(`smartPreview_${idx}`);
                if (!previewEl) return;
                
                if (!storeName) {
                    previewEl.innerHTML = `<span style="opacity: 0.5;">Awaiting branding...</span>`;
                    return;
                }

                // AI Logic Mirror
                let namePart = src.name.split('.').pop() ? src.name.replace(/\.[^/.]+$/, "") : src.name;
                const ext = src.name.split('.').pop();
                
                let detected = false;
                // Detect Patterns - use case-insensitive matching
                if (/\s+by\s+/i.test(namePart) || /\s+by-/i.test(namePart) || /\s*[-\|]\s*[a-zA-Z]/i.test(namePart)) {
                    detected = true;
                }

                let cleanedPart = namePart.replace(/\s+by\s+.*$/i, '').replace(/\s+by-.*$/i, '').replace(/\s*[-\|]\s*[a-zA-Z].*$/i, '').trim();
                const finalName = `${cleanedPart} by ${storeName}.${ext}`;
                
                previewEl.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: var(--primary); font-weight: 800; font-size: 0.8rem;">${finalName}</span>
                        ${detected ? `<span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: none; font-size: 0.55rem; padding: 1px 4px; animation: pulse 2s infinite;">AI CLEANED</span>` : ''}
                    </div>
                `;
            });
        };

        if (storeNameIn) {
            storeNameIn.oninput = updateSmartPreview;
            if (storeNameIn.value) updateSmartPreview(); // Run once if already filled
        }

        document.getElementById('sourceCoverageInfo').innerText = `${authorTaggedCount}/${sources.length} uploaded files contain embedded author tags`;
        document.getElementById('copyrightAssetCount').innerText = `${authorTaggedCount} embedded author tags found`;

        // Configure Form Interaction
        const sigInput = document.getElementById('sigInput');
        const sigFileName = document.getElementById('sigFileName');
        const clearSig = document.getElementById('clearSig');
        if (sigInput) sigInput.onchange = () => {
            if (sigInput.files.length > 0) {
                sigFileName.value = sigInput.files[0].name;
                clearSig.style.display = 'block';
            }
        };
        if (clearSig) clearSig.onclick = () => {
            sigInput.value = '';
            sigFileName.value = '';
            clearSig.style.display = 'none';
        };

        const picInput = document.getElementById('picInput');
        const picFileName = document.getElementById('picFileName');
        const clearPic = document.getElementById('clearPic');
        if (picInput) picInput.onchange = () => {
            if (picInput.files.length > 0) {
                picFileName.value = picInput.files[0].name;
                clearPic.style.display = 'block';
            }
        };
        if (clearPic) clearPic.onclick = () => {
            picInput.value = '';
            picFileName.value = '';
            clearPic.style.display = 'none';
        };

        const rebrandBtn = document.getElementById('rebrandBtn');
        if (rebrandBtn) rebrandBtn.onclick = handleRebrand;

        // Save Config → POST to API & store final_zip_name in state
        const saveConfigBtn = document.getElementById('saveConfigBtn');
        if (saveConfigBtn) {
            saveConfigBtn.onclick = async () => {
                saveConfigBtn.innerText  = 'Saving...';
                saveConfigBtn.disabled   = true;

                const fd = new FormData();
                const storeName = document.getElementById('storeNameIn')?.value?.trim() || '';
                fd.append('author_name',    document.getElementById('authorNameIn')?.value?.trim() || '');
                fd.append('store_name',     storeName);
                if (storeName) fd.append('final_zip_name', storeName);

                const picInput = document.getElementById('picInput');
                const sigInput = document.getElementById('sigInput');
                if (picInput?.files?.length > 0) fd.append('author_pic_file', picInput.files[0]);
                if (sigInput?.files?.length > 0) fd.append('signature_file',  sigInput.files[0]);

                try {
                    const res = await apiFetch(`/jobs/${state.jobId}/save-config`, {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    // Keep the resolved archive name for the download step
                    state.finalZipName = data.final_zip_name || data.store_name || '';
                    if (data.store_name)     state.storeName    = data.store_name;
                    if (data.author_name)    state.authorName   = data.author_name;

                    saveConfigBtn.innerText          = '✓ Config Saved';
                    saveConfigBtn.style.background   = 'rgba(16,185,129,0.2)';
                    saveConfigBtn.style.borderColor  = '#10b981';
                    saveConfigBtn.style.color        = '#10b981';

                    if (document.getElementById('autoProcessCheckbox')?.checked) {
                        handleRebrand();
                    }
                } catch (e) {
                    saveConfigBtn.innerText = 'Save Config';
                } finally {
                    saveConfigBtn.disabled = false;
                }
            };
        }

    }

    function renderReedit() {
        if (!state.outputs || !state.outputs.length || !state.manifest) return;
        
        const sourceList = document.getElementById('sourceFilesList');
        const sources = Array.isArray(state.manifest.source_files) ? state.manifest.source_files : [];
        
        let html = '';
        state.outputs.forEach((out, idx) => {
            const src = sources[idx] || { name: 'Unknown Source', size: 0, asset_count: 0, author_tags: [] };
            const authorTags = src.author_tags || [];
            
            html += `
                <div style="display: flex; flex-direction: column; gap: 12px; padding: 20px 24px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <span class="badge" style="font-size: 0.6rem; padding: 4px 10px; opacity: 0.8;">${(src.name || "").split('.').pop().toUpperCase()}</span>
                            <div>
                                <p style="font-weight: 800; font-size: 1rem; margin-bottom: 2px;">${src.name}</p>
                                <p style="font-size: 0.75rem; color: var(--text-dim); font-weight: 600;">${formatFileSize(src.size)} • ${src.asset_count || 0} assets</p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            ${authorTags.map(t => `<span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-dim); border: 1px solid var(--border-color); font-size: 0.65rem;">${t}</span>`).join('') || '<span class="badge" style="background: rgba(239, 68, 68, 0.05); color: #ef4444; border-color: rgba(239, 68, 68, 0.1); font-size: 0.65rem;">No Tags</span>'}
                        </div>
                    </div>
                    <div style="background: rgba(0,0,0,0.2); padding: 10px 16px; border-radius: 8px; border-left: 3px solid var(--primary); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <p style="font-size: 0.6rem; text-transform: uppercase; font-weight: 800; color: var(--text-dim); margin-bottom: 4px; letter-spacing: 0.05em;">Final Output Name</p>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="color: var(--primary); font-weight: 800; font-size: 0.8rem;">${out.name}</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="renameOutputFile('${out.name}')" style="padding: 6px 12px; font-size: 0.75rem; display: flex; align-items: center; gap: 6px;">
                            ✏️ Rename
                        </button>
                    </div>
                </div>
            `;
        });
        
        sourceList.innerHTML = html;
        document.getElementById('sourceCoverageInfo').innerText = "Ready to review and rename files manually";
    }

    window.renameOutputFile = async function(oldName) {
        const ext = oldName.split('.').pop();
        const oldNameWithoutExt = oldName.replace('.' + ext, '');
        let newName = prompt("Enter new filename (without extension):", oldNameWithoutExt);
        
        if (newName && newName.trim() !== '' && newName.trim() !== oldNameWithoutExt) {
            newName = newName.trim() + '.' + ext;
            
            try {
                const formData = new FormData();
                formData.append('old_name', oldName);
                formData.append('new_name', newName);
                
                const response = await apiFetch(`/jobs/${state.jobId}/rename-output`, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(await readErrorMessage(response, 'Rename failed'));
                }
                
                const data = await response.json();
                state.outputs = data.outputs;
                state.bundle = data.bundle;
                renderReedit();
                if (state.status === 'completed') {
                    renderOutputs();
                }
            } catch (err) {
                alert('Failed to rename: ' + err.message);
            }
        }
    };

    function renderOutputs() {
        const outCont = document.getElementById('outputContainer');
        const resolvedApiBase = getResolvedApiBase();
        const archiveName = state.bundle?.name || (state.finalZipName || state.storeName ? `${state.finalZipName || state.storeName}.zip` : '');
        const primaryDownloads = state.bundle ? [{
            name: state.bundle.name,
            size: state.bundle.size || 0,
            href: `${resolvedApiBase}/jobs/${state.jobId}/download`,
            meta: `${formatFileSize(state.bundle.size || 0)} • ZIP archive • Click to download`
        }] : state.outputs.map((f, i) => ({
            name: f.name,
            size: f.size,
            href: `${resolvedApiBase}/jobs/${state.jobId}/outputs/${i}/download`,
            meta: `${formatFileSize(f.size)} • Click to download`
        }));

        // Show configured filename info
        const infoHtml = archiveName ? `
            <div style="margin-bottom: 24px; padding: 16px 20px; background: rgba(249,115,22,0.06); border: 1px solid rgba(249,115,22,0.2); border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 1.4rem;">📦</span>
                <div style="flex: 1;">
                    <p style="font-size: 0.6rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">Output Archive Name</p>
                    <input id="finalDownloadNameIn" type="text" value="${archiveName}" class="text-input" style="background: rgba(0,0,0,0.3); height: 42px; font-weight: 800; width: 100%; border: 1px solid rgba(255,255,255,0.1); margin-top: 8px;">
                </div>
            </div>` : '';

        outCont.innerHTML = infoHtml + `
            <div class="file-grid" style="max-width: 600px; margin: 0 auto;">
                ${primaryDownloads.map(file => { const f = file; return `
                    <a href="${file.href}" class="file-item" style="text-decoration: none;">
                        <div class="file-icon">${getFileIcon(file.name)}</div>
                        <div class="file-info">
                            <div class="file-name">${file.name}</div>
                            <div class="file-meta">${formatFileSize(f.size)} • Click to download</div>
                        </div>
                    </a>
                `; }).join('')}
            </div>
        `;
        document.getElementById('downloadAllBtn').href = `${resolvedApiBase}/jobs/${state.jobId}/download`;
        
        setTimeout(() => {
            const finalNameIn = document.getElementById('finalDownloadNameIn');
            if (finalNameIn) {
                finalNameIn.oninput = () => {
                    let newName = finalNameIn.value.trim() || archiveName;
                    document.getElementById('downloadAllBtn').href = `${resolvedApiBase}/jobs/${state.jobId}/download?filename=${encodeURIComponent(newName)}`;
                };
            }
        }, 50);
    }

    elements.dropzone.onclick = () => elements.fileInput.click();
    elements.fileInput.onchange = (e) => {
        state.files = Array.from(e.target.files);
        renderFiles();
    };

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        elements.dropzone.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    elements.dropzone.addEventListener('drop', e => {
        state.files = Array.from(e.dataTransfer.files);
        renderFiles();
    }, false);

    async function handleUpload() {
        if (state.files.length === 0) return;
        state.status = 'uploading';
        updateView();

        try {
            const response = await uploadWithChunks(state.files);
            if (!response.ok) {
                state.status = 'error';
                elements.errorMessage.innerText = await readErrorMessage(response, 'Upload failed');
                updateView();
                return;
            }

            const data = await response.json();
            state.jobId = data.job_id;
            state.status = 'uploaded';
            updateView();
        } catch (error) {
            state.status = 'error';
            elements.errorMessage.innerText = error instanceof Error ? error.message : 'Upload request failed';
            updateView();
        }
    }

    elements.launchBtn.onclick = handleUpload;

    async function handleLinkUpload() {
        const url = document.getElementById('linkUploadInput').value.trim();
        if (!url) return alert('Please enter a secure file link (Google Drive or direct URL)');

        state.status = 'uploading';
        updateView();
        
        // Show cloud fetch specific messaging
        if (elements.stageTitle) elements.stageTitle.innerText = "Fetching Cloud File...";
        if (elements.stageMessage) elements.stageMessage.innerText = "Requesting remote node to secure the asset. This may take a moment depending on file size.";

        try {
            const response = await apiFetch('/upload-url', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url })
            });

            if (!response.ok) {
                state.status = 'error';
                elements.errorMessage.innerText = await readErrorMessage(response, 'Cloud import failed');
                updateView();
                return;
            }

            const data = await response.json();
            state.jobId = data.job_id;
            state.status = 'uploaded';
            updateView();
        } catch (error) {
            state.status = 'error';
            elements.errorMessage.innerText = error instanceof Error ? error.message : 'Cloud fetch request failed';
            updateView();
        }
    }

    const linkUploadBtn = document.getElementById('linkUploadBtn');
    if (linkUploadBtn) linkUploadBtn.onclick = handleLinkUpload;

    async function handleScan() {
        state.status = 'scanning';
        updateView();
        try {
            const formData = new FormData();
            formData.append('blocked_keywords', '[]');
            const resp = await apiFetch(`/jobs/${state.jobId}/scan`, { method: 'POST', body: formData });
            if (!resp.ok) throw new Error(await readErrorMessage(resp, "Branding scan failed to start"));
            pollStatus();
        } catch (e) {
            state.status = 'error';
            elements.errorMessage.innerText = e.message;
            updateView();
        }
    }

    async function handleRebrand() {
        const author = document.getElementById('authorNameIn').value;
        const store = document.getElementById('storeNameIn').value;
        const sigInput = document.getElementById('sigInput');
        const picInput = document.getElementById('picInput');

        const isAutoPilot = document.getElementById('globalAutoProcess')?.checked || document.getElementById('autoProcessCheckbox')?.checked;

        if (!isAutoPilot) {
            if (!author.trim() || !store.trim()) {
                alert("New Author Name and Store Name are required.");
                return;
            }

            if ((!sigInput.files || sigInput.files.length === 0) && !document.getElementById('sigFileName').value.includes('uploaded')) {
                alert("Please select a Signature (PNG) to rebrand.");
                return;
            }
            if ((!picInput.files || picInput.files.length === 0) && !document.getElementById('picFileName').value.includes('uploaded')) {
                alert("Please select an Author Pic (PNG) to rebrand.");
                return;
            }
        }

        state.status = 'processing';
        updateView();
        
        try {
            const formData = new FormData();
            formData.append('author_name', author);
            formData.append('store_name', store);
            if (store.trim()) formData.append('final_zip_name', store.trim());
            
            if (sigInput.files && sigInput.files.length > 0) {
                formData.append('signature_file', sigInput.files[0]);
            }
            if (picInput.files && picInput.files.length > 0) {
                formData.append('author_pic_file', picInput.files[0]);
            }

            const resp = await apiFetch(`/jobs/${state.jobId}/rebrand`, { method: 'POST', body: formData });
            if (!resp.ok) throw new Error(await readErrorMessage(resp, "Repackaging process failed"));
            pollStatus();
        } catch (e) {
            state.status = 'error';
            elements.errorMessage.innerText = e.message;
            updateView();
        }
    }

    // Removed old saveConfigBtn override

    let pollInterval;
    let pollFailCount = 0;
    function pollStatus() {
        clearInterval(pollInterval);
        pollFailCount = 0;
        pollInterval = setInterval(async () => {
            try {
                const resp = await apiFetch(`/jobs/${state.jobId}`);
                if (!resp.ok) throw new Error(await readErrorMessage(resp, "Connection lost"));
                const data = await resp.json();
                
                elements.progressPercent.innerText = (data.progress || 0) + "%";
                elements.progressFill.style.width = (data.progress || 0) + "%";
                if (data.progress_message) elements.stageMessage.innerText = data.progress_message;

                if (data.status === 'scanned') {
                    clearInterval(pollInterval);
                    state.status = 'scanned';
                    state.manifest = data.manifest;
                    updateView();
                    
                    if (document.getElementById('globalAutoProcess')?.checked) {
                        setTimeout(() => handleRebrand(), 500);
                    }
                } else if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    const isAuto = document.getElementById('globalAutoProcess')?.checked || document.getElementById('autoProcessCheckbox')?.checked;
                    state.status = isAuto ? 'completed' : 'reedit';
                    state.outputs = data.outputs;
                    state.bundle = data.bundle;
                    state.storeName = data.store_name || state.storeName || '';
                    state.finalZipName = data.final_zip_name || state.finalZipName || (data.bundle?.name ? data.bundle.name.replace(/\.zip$/i, '') : '');
                    updateView();

                    if (isAuto) {
                        setTimeout(() => {
                            const dlBtn = document.getElementById('downloadAllBtn');
                            if (dlBtn && dlBtn.href) {
                                window.location.href = dlBtn.href;
                            }
                        }, 1000);
                    }
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    state.status = 'error';
                    elements.errorMessage.innerText = data.error || "Processing failed.";
                    updateView();
                }
            } catch (e) {
                console.error(e);
                pollFailCount = (pollFailCount || 0) + 1;
                if (pollFailCount >= 3) {
                    clearInterval(pollInterval);
                    state.status = 'error';
                    elements.errorMessage.innerText = e.message || 'Lost connection to the backend. Please check the server and try again.';
                    updateView();
                }
            }
        }, 1500);
    }

    elements.processMoreBtn.onclick = elements.retryBtn.onclick = async () => {
        if (state.jobId) {
            // Silently trigger cleanup in the background
            apiFetch(`/jobs/${state.jobId}/cleanup`, { method: 'POST' }).catch(console.error);
        }
        state = { files: [], jobId: null, status: 'idle', manifest: null, outputs: [], bundle: null, storeName: '', finalZipName: '' };
        
        // Clear input fields
        if (document.getElementById('authorNameIn')) document.getElementById('authorNameIn').value = '';
        if (document.getElementById('storeNameIn')) document.getElementById('storeNameIn').value = '';
        if (document.getElementById('sigInput')) document.getElementById('sigInput').value = '';
        if (document.getElementById('sigFileName')) document.getElementById('sigFileName').value = '';
        if (document.getElementById('clearSig')) document.getElementById('clearSig').style.display = 'none';
        if (document.getElementById('picInput')) document.getElementById('picInput').value = '';
        if (document.getElementById('picFileName')) document.getElementById('picFileName').value = '';
        if (document.getElementById('clearPic')) document.getElementById('clearPic').style.display = 'none';
        if (document.getElementById('linkUploadInput')) document.getElementById('linkUploadInput').value = '';
        
        updateView();
    };

    elements.cleanupBtn.onclick = async () => {
        elements.cleanupBtn.disabled = true;
        elements.cleanupProgressWrap.style.display = 'block';
        elements.cleanupFeedback.innerText = "System maintenance in progress...";
        
        // Reset state & front-end UI immediately
        state = { files: [], jobId: null, status: 'idle', manifest: null, outputs: [], bundle: null, storeName: '', finalZipName: '' };
        
        // Clear input fields
        if (document.getElementById('authorNameIn')) document.getElementById('authorNameIn').value = '';
        if (document.getElementById('storeNameIn')) document.getElementById('storeNameIn').value = '';
        if (document.getElementById('sigInput')) document.getElementById('sigInput').value = '';
        if (document.getElementById('sigFileName')) document.getElementById('sigFileName').value = '';
        if (document.getElementById('clearSig')) document.getElementById('clearSig').style.display = 'none';
        if (document.getElementById('picInput')) document.getElementById('picInput').value = '';
        if (document.getElementById('picFileName')) document.getElementById('picFileName').value = '';
        if (document.getElementById('clearPic')) document.getElementById('clearPic').style.display = 'none';
        if (document.getElementById('linkUploadInput')) document.getElementById('linkUploadInput').value = '';
        
        updateView();

        try {
            const resp = await apiFetch(`/maintenance/cleanup-storage?deep_clean=true`, { method: 'POST' });
            if (!resp.ok) throw new Error(await readErrorMessage(resp, "Cleanup failed"));
            elements.cleanupProgressFill.style.width = "100%";
            elements.cleanupFeedback.innerText = "Memory and temporary records cleared.";
        } catch (e) {
            elements.cleanupFeedback.innerText = e.message || "System error during maintenance.";
        } finally {
            elements.cleanupBtn.disabled = false;
            setTimeout(() => {
                elements.cleanupProgressWrap.style.display = 'none';
                elements.cleanupProgressFill.style.width = "0%";
            }, 3000);
        }
    };

    // --- Storage Management Logic ---
    const storageUsageLabel = document.getElementById('storageUsageLabel');
    const storageBarFill = document.getElementById('storageBarFill');
    const clearMemoryBtn = document.getElementById('clearMemoryBtn');

    async function refreshStorageStats() {
        try {
            const resp = await apiFetch('/storage/stats');
            if (resp.ok) {
                const data = await resp.json();
                const usedGB = (data.used_bytes / (1024 * 1024 * 1024)).toFixed(2);
                const totalGB = (data.total_mb / 1024).toFixed(0);
                
                if (storageUsageLabel) storageUsageLabel.innerText = `${usedGB} GB / ${totalGB} GB (${data.percent}%)`;
                if (storageBarFill) {
                    storageBarFill.style.width = data.percent + "%";
                    
                    // Remove old status classes
                    storageBarFill.classList.remove('storage-status-normal', 'storage-status-warning', 'storage-status-full');
                    
                    // Add new status class
                    if (data.status === 'full') storageBarFill.classList.add('storage-status-full');
                    else if (data.status === 'warning') storageBarFill.classList.add('storage-status-warning');
                    else storageBarFill.classList.add('storage-status-normal');
                }

                // If storage is full, disable upload buttons
                if (data.status === 'full') {
                    if (elements.launchBtn) elements.launchBtn.disabled = true;
                    if (linkUploadBtn) linkUploadBtn.disabled = true;
                }
            }
        } catch (e) {
            console.error("Storage polling failed", e);
        }
    }

    if (clearMemoryBtn) {
        clearMemoryBtn.onclick = async () => {
            if (!confirm("Are you sure you want to clear all working memory? This will delete all uploaded and processed files.")) return;
            
            const originalText = clearMemoryBtn.innerHTML;
            clearMemoryBtn.disabled = true;
            clearMemoryBtn.innerHTML = "🧹 CLEARING...";
            
            try {
                const resp = await apiFetch(`/maintenance/cleanup-storage`, { method: 'POST' });
                if (resp.ok) {
                    refreshStorageStats();
                    // Reset UI to idle if currently processing
                    if (['uploading', 'scanning', 'processing'].includes(state.status)) {
                        state.status = 'idle';
                        updateView();
                    }
                    alert("Memory cleared successfully.");
                } else {
                    alert("Failed to clear memory.");
                }
            } catch (e) {
                alert("Error during memory cleanup.");
            } finally {
                clearMemoryBtn.disabled = false;
                clearMemoryBtn.innerHTML = originalText;
            }
        };
    }

    // Start Polling every 10 seconds
    setInterval(refreshStorageStats, 10000);
    refreshStorageStats(); // Initial check

    updateView();
</script>
@endpush
