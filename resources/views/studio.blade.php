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
  box-shadow: 0 0 30px rgba(249, 115, 22, 0.6);
  transform: scale(1.1);
}
.step.completed .step-circle {
  border-color: #10b981;
  background: #10b981;
  color: #000;
  box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
}
.step.clickable { cursor: pointer; }
.step.clickable:hover .step-circle {
  border-color: #f97316;
  transform: translateY(-4px) scale(1.05);
}
@keyframes slideUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
.fade-in { animation: slideUp 0.6s cubic-bezier(0.23, 1, 0.32, 1) forwards; }
.section-animate { animation: slideUp 0.8s cubic-bezier(0.23, 1, 0.32, 1) forwards; }
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
@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}
@keyframes glow {
  0%, 100% { box-shadow: 0 0 5px rgba(249, 115, 22, 0.5); }
  50% { box-shadow: 0 0 20px rgba(249, 115, 22, 0.8); }
}
.progress-bar {
  background: linear-gradient(90deg, #f97316, #fb923c, #f97316);
  background-size: 200% 100%;
  animation: shimmer 2s infinite linear;
  position: relative;
}
.progress-bar::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  box-shadow: 0 0 15px rgba(249, 115, 22, 0.5);
  animation: glow 2s infinite ease-in-out;
}
#progressPercent {
  text-shadow: 0 0 30px rgba(249, 115, 22, 0.3);
  transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.stat-box { transition: transform 0.3s ease, background 0.3s ease; }
.stat-box:hover { transform: translateY(-5px) scale(1.02); background: rgba(255,255,255,0.05) !important; }
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
.insights-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 40px;
}
.insight-card {
    margin: 0;
    padding: 22px;
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--border-color);
    min-width: 0;
    min-height: 142px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.insight-authors {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    min-height: 40px;
    align-content: flex-start;
}
.insight-value-large {
    font-size: 2.35rem;
    font-weight: 900;
    color: var(--text-main);
    line-height: 1;
}
.insight-value-success {
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--success);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
@media (max-width: 980px) {
    .insights-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 640px) {
    .insights-grid {
        grid-template-columns: 1fr;
    }
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
            <span class="storage-title storage-label-text" id="storageLabelText">{{ (bool) config('services.google.owner_managed') ? 'Google Drive Usage' : 'Engine Storage Usage' }}</span>
            <span class="storage-usage-text" id="storageUsageLabel">Loading...</span>
        </div>
        <div class="storage-bar-container">
            <div id="storageBarFill" class="storage-bar-fill storage-status-normal"></div>
        </div>
        <p id="storageUsageNote" class="drop-subtext" style="margin-top: 10px; font-size: 0.72rem; color: var(--text-dim);">Fetching storage usage...</p>
        @if(auth()->user()->is_admin)
            <a id="storageRootFolderLink" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="margin-top: 12px; display: none; width: fit-content;">Open Drive Storage Folder</a>
        @endif
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
                <span class="drop-icon" style="pointer-events: none;"><i class="fa-solid fa-folder-open" aria-hidden="true"></i></span>
                <p class="drop-text" style="pointer-events: none;">Drag files here or click to browse</p>
                <p class="drop-subtext" style="pointer-events: none; margin-top: 8px;">Supports .brushset, .brush, .procreate, .swatches, .usdz, and .zip</p>
            </div>
            <input id="fileInput" type="file" multiple hidden accept=".brushset,.brush,.procreate,.swatches,.usdz,.zip" onclick="event.stopPropagation()">

            <!-- LINK UPLOAD -->
            <div class="studio-card" style="margin: 0; padding: 32px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); display: flex; flex-direction: column; justify-content: center;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                    <div style="padding: 10px; background: rgba(249, 115, 22, 0.1); border-radius: 12px; font-size: 1.5rem; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-link"></i></div>
                    <div>
                        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800;">Cloud Import</h3>
                        <p class="drop-subtext" style="font-size: 0.75rem; color: var(--text-muted);">Paste Google Drive or direct links</p>
                    </div>
                </div>
                
                <div class="control-group" style="margin-bottom: 20px;">
                    <input type="text" id="linkUploadInput" class="text-input" placeholder="https://drive.google.com/..." style="background: rgba(0,0,0,0.3); height: 52px; font-size: 0.85rem;">
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="button" id="linkUploadBtn" class="btn btn-secondary" style="height: 52px; flex: 1; font-weight: 800; border-color: rgba(249, 115, 22, 0.3); color: var(--primary);">
                        Download & Import
                    </button>
                    <button type="button" id="googleDriveBtn" class="btn btn-secondary" style="height: 52px; width: 60px; padding: 0; background: rgba(66, 133, 244, 0.1); border-color: rgba(66, 133, 244, 0.3); color: #4285f4; display: flex; align-items: center; justify-content: center;">
                        <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: currentColor;">
                            <path d="M7.74 2L1 14l3.37 6h13.26L23 8h-6.63L7.74 2zM15 8h6.63l-3.37 6H15V8zM5.53 14H15l-3.37 6H4.37L5.53 14z"/>
                        </svg>
                    </button>
                </div>
                
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
                <p id="statSpeedLabel" class="control-label" style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Upload Speed</p>
                <p id="statSpeed" style="font-weight: 800; font-size: 1.1rem; color: var(--text-main);">Calculating...</p>
            </div>
            <div class="stat-box" style="background: rgba(255,255,255,0.02); padding: 20px 16px; border-radius: 16px; border: 1px solid var(--border-color); backdrop-filter: blur(10px);">
                <p id="statTransferredLabel" class="control-label" style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Transferred</p>
                <p id="statTransferred" style="font-weight: 800; font-size: 1.1rem; color: var(--text-main);">0 B / 0 B</p>
            </div>
            <div class="stat-box" style="background: rgba(255,255,255,0.02); padding: 20px 16px; border-radius: 16px; border: 1px solid var(--border-color); backdrop-filter: blur(10px);">
                <p id="statEtaLabel" class="control-label" style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">ETA</p>
                <p id="statEta" style="font-weight: 800; font-size: 1.1rem; color: var(--text-main);">Calculating...</p>
            </div>
            <div class="stat-box" style="background: rgba(255,255,255,0.02); padding: 20px 16px; border-radius: 16px; border: 1px solid var(--border-color); backdrop-filter: blur(10px);">
                <p id="statFilesLabel" class="control-label" style="font-size: 0.65rem; color: var(--text-dim); margin-bottom: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Files</p>
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
        <div class="insights-grid">
            <div class="studio-card insight-card">
                <p class="control-label" style="font-size: 0.7rem; margin-bottom: 12px; opacity: 0.6;">DETECTED AUTHORS</p>
                <div id="detectedAuthorsList" class="insight-authors">
                    <!-- Authors injected here -->
                </div>
            </div>
            <div class="studio-card insight-card">
                <p class="control-label" style="font-size: 0.7rem; margin-bottom: 12px; opacity: 0.6;">TOTAL ASSETS FOUND</p>
                <div id="totalAssetsCount" class="insight-value-large">0</div>
            </div>
            <div class="studio-card insight-card">
                <p class="control-label" style="font-size: 0.7rem; margin-bottom: 12px; opacity: 0.6;">UPLOADED FILES</p>
                <div id="uploadedFilesCount" class="insight-value-large">0</div>
            </div>
            <div class="studio-card insight-card">
                <p class="control-label" style="font-size: 0.7rem; margin-bottom: 12px; opacity: 0.6;">INTEGRITY STATUS</p>
                <div class="insight-value-success">
                    Verified <span style="font-size: 1rem; opacity: 0.8;"><i class="fa-solid fa-box-check"></i></span>
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
                        <button type="button" onclick="document.getElementById('sigInput').click()" class="btn btn-secondary" style="height: 52px; width: 60px; padding: 0; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-folder-open" aria-hidden="true"></i></button>
                    </div>
                    <input id="sigInput" type="file" hidden accept=".png">
                    <p id="clearSig" class="drop-subtext" style="font-size: 0.65rem; color: var(--primary); cursor: pointer; text-transform: uppercase; margin-top: 8px; display: none;">Clear Signature</p>
                </div>
                <div class="control-group">
                    <label class="control-label" style="font-size: 0.65rem; letter-spacing: 0.05em;">AUTHOR PIC (PNG) <span style="opacity: 0.5;">- required</span></label>
                    <div style="display: flex; gap: 8px;">
                        <input id="picFileName" class="text-input" type="text" readonly placeholder="No file selected" style="background: rgba(0,0,0,0.3); height: 52px; flex: 1; font-size: 0.8rem; opacity: 0.7;">
                        <button type="button" onclick="document.getElementById('picInput').click()" class="btn btn-secondary" style="height: 52px; width: 60px; padding: 0; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-folder-open" aria-hidden="true"></i></button>
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
        <div class="logo-icon" style="margin: 0 auto 24px; background: var(--success);"><i class="fa-solid fa-check"></i></div>
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



@endsection

@push('scripts')
<script>
    const CONFIGURED_API_BASE = @json(config('services.backend.url'));
    const GOOGLE_DRIVE_CONFIG = {
        apiKey: '{{ config("services.google.api_key") }}',
        clientId: '{{ config("services.google.client_id") }}',
        appId: '{{ config("services.google.app_id") }}'
    };
    const OWNER_MANAGED_DRIVE = @json((bool) config('services.google.owner_managed'));
    const GOOGLE_DRIVE_SCOPES = [
        'https://www.googleapis.com/auth/drive.file',
        'https://www.googleapis.com/auth/drive.metadata.readonly'
    ].join(' ');

    let pickerApiLoaded = false;
    window.oauthToken = null;

    // Load the Google API Loader script
    function loadGoogleApi() {
        const script = document.createElement('script');
        script.src = 'https://apis.google.com/js/api.js';
        script.onload = () => gapi.load('picker', {callback: onPickerApiLoad});
        document.body.appendChild(script);

        const gapiScript = document.createElement('script');
        gapiScript.src = 'https://accounts.google.com/gsi/client';
        gapiScript.onload = () => {};
        document.body.appendChild(gapiScript);
    }

    function onPickerApiLoad() {
        pickerApiLoaded = true;
    }

    function showGoogleDriveAccessError(detail = '') {
        const rawDetail = String(detail || '').trim();
        const normalizedDetail = rawDetail.toLowerCase();
        const testingModeHint = normalizedDetail.includes('access_denied')
            || normalizedDetail.includes('consent')
            || normalizedDetail.includes('test')
            || normalizedDetail.includes('unverified');
        const originHint = normalizedDetail.includes('origin');
        const popupHint = normalizedDetail.includes('popup') || normalizedDetail.includes('closed');
        const detailSuffix = rawDetail ? ` Details: ${rawDetail}` : '';

        state.status = 'error';
        elements.errorMessage.innerText = testingModeHint
            ? 'Google Drive access was blocked. If your OAuth consent screen is still in Testing, add the same Google account under Test users in Google Cloud Console and try again.'
            : originHint
                ? 'Google rejected this app origin. Add the exact URL you are opening in the browser to Authorized JavaScript origins in the Google OAuth client, then try again.'
                : popupHint
                    ? 'Google sign-in popup was closed or blocked before authorization finished. Reopen it and allow the popup to continue.'
                    : 'Google Drive authorization failed. Verify the OAuth consent screen, authorized JavaScript origins, and selected Google account, then try again.';
        if (detailSuffix) {
            elements.errorMessage.innerText += detailSuffix;
        }
        console.error('Google Drive auth error:', rawDetail || 'unknown');
        updateView();
    }

    function handleDriveAction() {
        if (!GOOGLE_DRIVE_CONFIG.clientId || !GOOGLE_DRIVE_CONFIG.apiKey) {
            alert("Google Drive is not fully configured. Please set GOOGLE_DRIVE_CLIENT_ID and GOOGLE_DRIVE_API_KEY in .env");
            return;
        }

        if (!window.google?.accounts?.oauth2) {
            showGoogleDriveAccessError('google_identity_services_not_loaded');
            return;
        }

        const tokenClient = google.accounts.oauth2.initTokenClient({
            client_id: GOOGLE_DRIVE_CONFIG.clientId,
            scope: GOOGLE_DRIVE_SCOPES,
            callback: (response) => {
                if (response.error !== undefined) {
                    showGoogleDriveAccessError(response.error_description || response.error);
                    return;
                }
                window.oauthToken = response.access_token;
                refreshStorageStats(); // Refresh stats immediately after login
                createPicker();
            },
            error_callback: (error) => {
                showGoogleDriveAccessError(error?.message || error?.type || 'oauth_request_failed');
            },
        });

        try {
            if (oauthToken === null) {
                tokenClient.requestAccessToken({prompt: 'consent'});
            } else {
                tokenClient.requestAccessToken({prompt: ''});
            }
        } catch (error) {
            showGoogleDriveAccessError(error?.message || 'oauth_request_failed');
        }
    }

    function createPicker() {
        if (pickerApiLoaded && oauthToken) {
            const view = new google.picker.View(google.picker.ViewId.DOCS);
            view.setMimeTypes("application/zip,application/x-zip-compressed,application/octet-stream");
            const picker = new google.picker.PickerBuilder()
                .enableFeature(google.picker.Feature.NAV_HIDDEN)
                .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
                .setAppId(GOOGLE_DRIVE_CONFIG.appId)
                .setOAuthToken(oauthToken)
                .addView(view)
                .addView(new google.picker.DocsUploadView())
                .setDeveloperKey(GOOGLE_DRIVE_CONFIG.apiKey)
                .setCallback(pickerCallback)
                .build();
            picker.setVisible(true);
        }
    }

    async function pickerCallback(data) {
        if (data[google.picker.Response.ACTION] === google.picker.Action.PICKED) {
            const docs = data[google.picker.Response.DOCUMENTS] || [];
            if (!Array.isArray(docs) || docs.length === 0) {
                return;
            }

            state.status = 'uploading';
            updateView();
            
            if (elements.stageTitle) elements.stageTitle.innerText = "Connecting Google Drive...";
            if (elements.stageMessage) elements.stageMessage.innerText = docs.length === 1
                ? "Securely fetching \"" + docs[0].name + "\" from your cloud storage."
                : `Securely fetching ${docs.length} files from your cloud storage.`;

            try {
                const response = await apiFetch('/drive-upload/complete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        drive_files: docs.map((doc) => ({
                            id: doc[google.picker.Document.ID],
                            name: doc[google.picker.Document.NAME] || doc.name || 'Google Drive file',
                        })),
                        drive_token: oauthToken || ''
                    })
                });

                if (!response.ok) {
                    clearInterval(pollInterval);
                    state.status = 'error';
                    elements.errorMessage.innerText = await readErrorMessage(response, 'Google Drive import failed');
                    updateView();
                    return;
                }

                const resData = await response.json();
                state.jobId = resData.job_id;
                state.driveStorage = resData.drive_storage || state.driveStorage;
                state.uploadedFileCount = docs.length;
                state.uploadedFileNames = docs.map((doc) => doc[google.picker.Document.NAME] || doc.name || 'Google Drive file');
                state.status = 'uploaded';
                updateView();
            } catch (error) {
                clearInterval(pollInterval);
                state.status = 'error';
                elements.errorMessage.innerText = error.message || 'Drive fetch request failed';
                updateView();
            }
        }
    }

    // Initialize Google API loading
    document.addEventListener('DOMContentLoaded', loadGoogleApi);

    const googleDriveBtn = document.getElementById('googleDriveBtn');
    if (googleDriveBtn) {
        if (OWNER_MANAGED_DRIVE) {
            googleDriveBtn.style.display = 'none';
        } else {
            googleDriveBtn.onclick = handleDriveAction;
        }
    }
    const normalizeBase = (value) => value ? value.replace(/\/$/, '') : '';
    const shouldRetryWithFallback = (status) => [404, 500, 502, 503, 504].includes(status);

    // Point to local Laravel engine by default
    const LARAVEL_ENGINE_BASE = '{{ url("/studio-engine") }}';
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

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getFilenameParts(filename) {
        const safeName = String(filename ?? '');
        const lastDotIndex = safeName.lastIndexOf('.');

        if (lastDotIndex <= 0) {
            return { base: safeName, extension: '' };
        }

        return {
            base: safeName.slice(0, lastDotIndex),
            extension: safeName.slice(lastDotIndex + 1),
        };
    }

    function buildUploadFormData(files) {
        const formData = new FormData();
        files.forEach(file => formData.append('files[]', file, file.name));
        return formData;
    }

    const CHUNK_SIZE = 16 * 1024 * 1024; // 16MB chunks reduce request overhead on shared hosting

    function updateUploadProgressUI(currentUploaded, totalSize, completedFiles, totalFiles, startTime) {
        const safeTotal = Math.max(totalSize || 0, 1);
        const percent = Math.min(99, Math.round((currentUploaded / safeTotal) * 100));

        if (elements.progressPercent) elements.progressPercent.innerText = percent + "%";
        if (elements.progressFill) elements.progressFill.style.width = percent + "%";

        const elapsed = (Date.now() - startTime) / 1000;
        const speed = currentUploaded / (elapsed || 0.1);
        const remaining = Math.max(0, totalSize - currentUploaded);
        const eta = speed > 0 ? Math.ceil(remaining / speed) : 0;

        if (elements.statSpeed) elements.statSpeed.innerText = formatFileSize(speed) + "/s";
        if (elements.statTransferred) elements.statTransferred.innerText = `${formatFileSize(currentUploaded)} / ${formatFileSize(totalSize)}`;
        if (elements.statFiles) elements.statFiles.innerText = `${completedFiles} / ${totalFiles}`;
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

    async function uploadViaManagedDrive(files) {
        const totalSize = files.reduce((acc, f) => acc + f.size, 0);
        const startTime = Date.now();
        let uploadedBytes = 0;
        let completedFiles = 0;

        if (elements.stageTitle) elements.stageTitle.innerText = "Uploading to Google Drive...";
        if (elements.stageMessage) elements.stageMessage.innerText = "Sending files to managed cloud storage before processing.";

        const initResp = await apiFetch('/drive-upload/init', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                files: files.map((file) => ({
                    name: file.name,
                    size: file.size,
                    type: file.type || 'application/octet-stream'
                })),
                drive_token: window.oauthToken || ''
            })
        });

        if (!initResp.ok) {
            throw new Error(await readErrorMessage(initResp, 'Managed Google Drive upload initialization failed'));
        }

        const initData = await initResp.json();
        const uploads = Array.isArray(initData.uploads) ? initData.uploads : [];
        if (!initData.job_id || uploads.length !== files.length) {
            throw new Error('Managed Google Drive upload initialization returned incomplete session data');
        }

        state.jobId = initData.job_id;
        state.driveStorage = initData.drive_storage || state.driveStorage;

        const uploadedDriveFiles = [];

        for (let index = 0; index < files.length; index++) {
            const file = files[index];
            const upload = uploads[index];

            const payload = await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('PUT', upload.upload_url);
                xhr.responseType = 'json';
                xhr.setRequestHeader('Content-Type', file.type || upload.type || 'application/octet-stream');

                xhr.upload.onprogress = (event) => {
                    if (!event.lengthComputable) {
                        return;
                    }

                    updateUploadProgressUI(uploadedBytes + event.loaded, totalSize, completedFiles, files.length, startTime);
                };

                xhr.onload = () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        let responsePayload = xhr.response;
                        if (!responsePayload && xhr.responseText) {
                            try {
                                responsePayload = JSON.parse(xhr.responseText);
                            } catch (error) {
                                responsePayload = {};
                            }
                        }
                        resolve(responsePayload || {});
                        return;
                    }

                    reject(new Error(`Managed Google Drive upload failed (${xhr.status})`));
                };

                xhr.onerror = () => reject(new Error('Network error during managed Google Drive upload'));
                xhr.send(file);
            });

            uploadedBytes += file.size;
            completedFiles += 1;
            updateUploadProgressUI(uploadedBytes, totalSize, completedFiles, files.length, startTime);

            uploadedDriveFiles.push({
                id: payload.id,
                name: payload.name || file.name,
                size: Number(payload.size || file.size),
                webViewLink: payload.webViewLink || null,
                webContentLink: payload.webContentLink || null,
            });
        }

        if (elements.stageTitle) elements.stageTitle.innerText = "Importing into Engine...";
        if (elements.stageMessage) elements.stageMessage.innerText = "Fetching your Drive files into the processing workspace.";

        const completeResp = await apiFetch('/drive-upload/complete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                job_id: initData.job_id,
                drive_files: uploadedDriveFiles,
                drive_token: window.oauthToken || ''
            })
        });

        if (!completeResp.ok) {
            throw new Error(await readErrorMessage(completeResp, 'Managed Google Drive import failed'));
        }

        return completeResp;
    }

    async function uploadWithChunks(files) {
        const jobId = typeof crypto.randomUUID === 'function' ? crypto.randomUUID() : Math.random().toString(36).substring(2);
        const totalSize = files.reduce((acc, f) => acc + f.size, 0);
        let uploadedBytes = 0;
        const startTime = Date.now();
        let completedFiles = 0;

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
                formData.append('chunk', chunk, 'chunk.blob');

                const response = await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', `${activeApiBase}/upload-chunk`);
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
                    
                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            updateUploadProgressUI(uploadedBytes + e.loaded, totalSize, completedFiles, files.length, startTime);
                        }
                    };

                    xhr.onload = () => resolve(new Response(xhr.responseText, { status: xhr.status }));
                    xhr.onerror = () => reject(new Error('Network error during chunk upload'));
                    xhr.send(formData);
                });

                if (!response.ok) {
                    let msg = 'Failed to upload chunk';
                    try {
                        const err = await response.json();
                        if (err.error) msg = err.error;
                    } catch(e) {}
                    throw new Error(`${msg} (${response.status})`);
                }
                uploadedBytes += (end - start);
            }
            completedFiles += 1;
            updateUploadProgressUI(uploadedBytes, totalSize, completedFiles, files.length, startTime);
        }

        // Finalize
        const finalizeResp = await apiFetch('/finalize-upload', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                job_id: jobId,
                drive_token: window.oauthToken || ''
            })
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
        finalZipName: '',
        driveStorage: null,
        progressValue: 0,
        progressMeta: null,
        uploadedFileCount: 0,
        uploadedFileNames: [],
        lastBackendProgressAt: 0,
        renamingOutputIndex: null,
        renameDraftValue: '',
        renameSavingIndex: null,
        renameErrorMessage: ''
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
        statSpeedLabel: document.getElementById('statSpeedLabel'),
        statSpeed: document.getElementById('statSpeed'),
        statTransferredLabel: document.getElementById('statTransferredLabel'),
        statTransferred: document.getElementById('statTransferred'),
        statEtaLabel: document.getElementById('statEtaLabel'),
        statEta: document.getElementById('statEta'),
        statFilesLabel: document.getElementById('statFilesLabel'),
        statFiles: document.getElementById('statFiles'),
        
        assetSummary: document.getElementById('assetSummary'),
        errorMessage: document.getElementById('errorMessage'),
        retryBtn: document.getElementById('retryBtn'),
        processMoreBtn: document.getElementById('processMoreBtn'),
        
        backToUploadBtn: document.getElementById('backToUploadBtn'),
        backToScannedBtn: document.getElementById('backToScannedBtn')
    };

    let stageActivityTimer = null;
    let previewLoadObserver = null;
    let storageStatsTimer = null;
    let storageStatsInFlight = false;
    let storageStatsFailCount = 0;

    function setProgressDisplay(percent) {
        const safePercent = Math.max(0, Math.min(100, Math.round(Number(percent) || 0)));
        state.progressValue = safePercent;
        if (elements.progressPercent) elements.progressPercent.innerText = `${safePercent}%`;
        if (elements.progressFill) elements.progressFill.style.width = `${safePercent}%`;
    }

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

    function formatStorageAmount(bytes) {
        if (!bytes || bytes <= 0) return "0 GB";
        const gb = bytes / (1024 * 1024 * 1024);
        if (gb >= 1024) {
            const tb = gb / 1024;
            return `${tb.toFixed(tb >= 10 ? 0 : 2)} TB`;
        }

        return `${gb.toFixed(gb >= 100 ? 0 : gb >= 10 ? 1 : 2)} GB`;
    }

    function formatElapsed(seconds) {
        const totalSeconds = Math.max(0, Math.floor(Number(seconds) || 0));
        const mins = Math.floor(totalSeconds / 60);
        const secs = totalSeconds % 60;
        return mins > 0 ? `${mins}m ${secs}s` : `${secs}s`;
    }

    function summarizeName(value, max = 24) {
        const text = String(value || '').trim();
        if (!text) return 'Preparing...';
        return text.length > max ? `${text.slice(0, max - 1)}...` : text;
    }

    function setProgressStats(stats) {
        const mappings = [
            ['statSpeedLabel', 'statSpeed', stats[0]],
            ['statTransferredLabel', 'statTransferred', stats[1]],
            ['statEtaLabel', 'statEta', stats[2]],
            ['statFilesLabel', 'statFiles', stats[3]],
        ];

        mappings.forEach(([labelKey, valueKey, card]) => {
            if (!card) return;
            if (elements[labelKey]) elements[labelKey].innerText = card.label;
            if (elements[valueKey]) elements[valueKey].innerText = card.value;
        });
    }

    function getStageElapsedSeconds() {
        return stageActivityTimer && stageActivityTimer.startedAt
            ? Math.floor((Date.now() - stageActivityTimer.startedAt) / 1000)
            : 0;
    }

    function stopStageActivity() {
        if (stageActivityTimer?.intervalId) {
            clearInterval(stageActivityTimer.intervalId);
        }
        stageActivityTimer = null;
    }

    function updateScanningStats(meta = null) {
        const totalFiles = Number(meta?.total_files || state.uploadedFileCount || state.files.length || 1);
        const currentFileIndex = Math.min(totalFiles, Math.max(1, Number(meta?.current_file_index || 1)));
        const currentFileName = meta?.current_file_name || state.uploadedFileNames[currentFileIndex - 1] || state.uploadedFileNames[0] || 'Package';
        const assetsFound = Number(meta?.assets_found || 0);
        const detectedAuthors = Number(meta?.detected_authors || 0);
        const elapsed = meta?.elapsed_seconds ?? getStageElapsedSeconds();

        setProgressStats([
            { label: 'Source Files', value: `${totalFiles}` },
            { label: 'Current File', value: `${currentFileIndex} / ${totalFiles}` },
            { label: 'Elapsed', value: formatElapsed(elapsed) },
            { label: 'Detected', value: assetsFound > 0 ? `${assetsFound} assets${detectedAuthors > 0 ? ` • ${detectedAuthors} author` : ''}` : 'Scanning...' },
        ]);

        if (elements.stageTitle) elements.stageTitle.innerText = 'Analyzing Package...';
        if (elements.stageMessage) {
            if (meta?.current_file_name) {
                elements.stageMessage.innerText = `Scanning ${currentFileIndex}/${totalFiles}: ${summarizeName(meta.current_file_name, 56)}`;
            } else {
                const elapsedSeconds = Number(elapsed || 0);
                const phaseMessage = elapsedSeconds < 6
                    ? 'Opening archive and indexing folders.'
                    : elapsedSeconds < 14
                        ? 'Reading binary plists and embedded metadata.'
                        : 'Cataloging preview assets and author signatures.';
                elements.stageMessage.innerText = `${phaseMessage} ${summarizeName(currentFileName, 40)}`;
            }
        }
    }

    function updateProcessingStats(meta = null) {
        const totalFiles = Number(meta?.total_files || state.manifest?.source_files?.length || state.uploadedFileCount || 1);
        const currentFileIndex = Math.min(totalFiles, Math.max(1, Number(meta?.current_file_index || 1)));
        const currentFileName = meta?.current_file_name || state.uploadedFileNames[currentFileIndex - 1] || state.uploadedFileNames[0] || 'Package';
        const elapsed = meta?.elapsed_seconds ?? getStageElapsedSeconds();
        const action = String(meta?.action || 'Rebrand + Zip');

        setProgressStats([
            { label: 'Source Files', value: `${totalFiles}` },
            { label: 'Current File', value: `${currentFileIndex} / ${totalFiles}` },
            { label: 'Elapsed', value: formatElapsed(elapsed) },
            { label: 'Action', value: summarizeName(action, 24) },
        ]);

        if (elements.stageTitle) elements.stageTitle.innerText = 'Applying Rebrand...';
        if (elements.stageMessage) {
            elements.stageMessage.innerText = meta?.current_file_name
                ? `Processing ${currentFileIndex}/${totalFiles}: ${summarizeName(meta.current_file_name, 56)}`
                : 'Rewriting metadata, replacing identity assets, and packaging results.';
        }
    }

    function startStageActivity(mode) {
        stopStageActivity();

        stageActivityTimer = {
            mode,
            startedAt: Date.now(),
            intervalId: window.setInterval(() => {
                const backendFresh = (Date.now() - (state.lastBackendProgressAt || 0)) < 2200;
                if (backendFresh) {
                    return;
                }

                if (mode === 'scanning') {
                    const elapsed = getStageElapsedSeconds();
                    const pseudoPercent = Math.min(93, 3 + Math.floor(Math.sqrt(elapsed + 1) * 12));
                    if (pseudoPercent > state.progressValue) {
                        setProgressDisplay(pseudoPercent);
                    }
                    updateScanningStats(state.progressMeta);
                } else if (mode === 'processing') {
                    const elapsed = getStageElapsedSeconds();
                    const pseudoPercent = Math.min(97, 8 + Math.floor(Math.sqrt(elapsed + 1) * 10));
                    if (pseudoPercent > state.progressValue) {
                        setProgressDisplay(pseudoPercent);
                    }
                    updateProcessingStats(state.progressMeta);
                }
            }, 500)
        };
    }

    function shouldRenderAssetPreview(asset) {
        const path = String(asset?.rel_path || asset?.file || '').toLowerCase();
        const name = String(asset?.name || path.split('/').pop() || '').toLowerCase();

        if (!/\.(png|jpg|jpeg|gif|webp)$/i.test(path)) {
            return false;
        }

        // Skip heavy texture maps on the initial grid to keep refresh fast.
        if (name === 'grain.png' || name === 'shape.png') {
            return false;
        }

        return true;
    }

    function getRenderablePreviewAssets(assets) {
        return assets.filter(shouldRenderAssetPreview).slice(0, 24);
    }

    function revealPreviewImage(img) {
        if (!img || img.dataset.previewLoaded === 'true') {
            return;
        }

        const previewSrc = img.dataset.previewSrc;
        if (!previewSrc) {
            return;
        }

        img.dataset.previewLoaded = 'true';
        img.src = previewSrc;
    }

    function initializeAssetPreviews() {
        if (previewLoadObserver) {
            previewLoadObserver.disconnect();
            previewLoadObserver = null;
        }

        const previewImages = Array.from(document.querySelectorAll('[data-asset-preview]'));
        if (previewImages.length === 0) {
            return;
        }

        previewImages.forEach((img, index) => {
            if (index < 6) {
                img.loading = 'eager';
                img.fetchPriority = index < 3 ? 'high' : 'auto';
                revealPreviewImage(img);
            }
        });

        if (!('IntersectionObserver' in window)) {
            previewImages.slice(6).forEach(revealPreviewImage);
            return;
        }

        previewLoadObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                revealPreviewImage(entry.target);
                previewLoadObserver?.unobserve(entry.target);
            });
        }, {
            rootMargin: '220px 0px',
            threshold: 0.01,
        });

        previewImages.slice(6).forEach((img) => {
            img.loading = 'lazy';
            img.fetchPriority = 'low';
            previewLoadObserver.observe(img);
        });
    }

    function getDriveStorageStatusText() {
        if (!state.driveStorage) return '';
        if (state.driveStorage.status === 'error') return state.driveStorage.error || 'Drive sync failed';
        if (state.driveStorage.status === 'out_of_sync') return state.driveStorage.error || 'Drive copy needs resync';
        if (state.driveStorage.job_folder_url) return 'Google Drive storage is connected for this job';
        return '';
    }

    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        switch(ext) {
            case 'brush':
            case 'brushset': return '<i class="fa-solid fa-palette"></i>';
            case 'procreate': return '<i class="fa-solid fa-image"></i>';
            case 'swatches': return '<i class="fa-solid fa-rainbow"></i>';
            case 'usdz': return '<i class="fa-solid fa-box"></i>';
            default: return '<i class="fa-solid fa-file"></i>';
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

        if (!['uploading', 'scanning', 'processing'].includes(state.status)) {
            stopStageActivity();
        }

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
                setProgressStats([
                    { label: 'Upload Speed', value: 'Calculating...' },
                    { label: 'Transferred', value: '0 B / 0 B' },
                    { label: 'ETA', value: 'Calculating...' },
                    { label: 'Files', value: '0 / 0' },
                ]);
            } else if (state.status === 'scanning') {
                s1.classList.add('completed');
                s2.classList.add('active');
                elements.stageBadge.innerText = "STAGE 2";
                updateScanningStats(state.progressMeta);
            } else if (state.status === 'processing') {
                [s1, s2, s3].forEach(s => s.classList.add('completed'));
                s4.classList.add('active');
                elements.stageBadge.innerText = "STAGE 4";
                updateProcessingStats(state.progressMeta);
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
        const previewAssets = getRenderablePreviewAssets(assets);
        const totalPreviewableAssets = assets.filter(shouldRenderAssetPreview).length;
        const hiddenPreviewCount = Math.max(0, totalPreviewableAssets - previewAssets.length);
        
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
        previewGrid.innerHTML = previewAssets.map(asset => {
            const path = asset.rel_path || "";
            const isImage = /\.(png|jpg|jpeg|gif|webp)$/i.test(path);
            const resolvedApiBase = getResolvedApiBase();
            const previewUrl = isImage ? `${resolvedApiBase}/jobs/${state.jobId}/assets/preview?path=${encodeURIComponent(path)}` : null;
            const downloadUrl = `${resolvedApiBase}/jobs/${state.jobId}/assets/${encodeURIComponent(path)}`;
            
            return `
                <div class="studio-card" style="margin: 0; padding: 12px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); overflow: hidden;">
                    <div style="aspect-ratio: 4/3; background: #111; background-image: linear-gradient(45deg, #181818 25%, transparent 25%), linear-gradient(-45deg, #181818 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #181818 75%), linear-gradient(-45deg, transparent 75%, #181818 75%); background-size: 20px 20px; background-position: 0 0, 0 10px, 10px -10px, -10px 0px; border-radius: 8px; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                        ${previewUrl ? `
                            <img data-asset-preview="true" data-preview-src="${previewUrl}" decoding="async" alt="${asset.name || path.split('/').pop()}" style="max-width: 100%; max-height: 100%; object-fit: contain; opacity: 0; transition: opacity 0.2s ease; position: relative; z-index: 1;" onload="this.style.opacity='1'; const status=this.parentElement.querySelector('[data-preview-status]'); if(status) status.remove();" onerror="this.remove(); const status=this.parentElement.querySelector('[data-preview-status]'); if(status) status.innerText='Preview unavailable';">
                            <span data-preview-status style="position: absolute; inset: auto 12px 12px 12px; font-size: 0.7rem; font-weight: 700; text-align: center; color: rgba(255,255,255,0.8); background: rgba(0,0,0,0.55); border: 1px solid rgba(255,255,255,0.08); border-radius: 999px; padding: 6px 10px; z-index: 2;">Loading preview...</span>
                        ` : `<span style="font-size: 2rem;">${getFileIcon(path)}</span>`}
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
        if (hiddenPreviewCount > 0) {
            previewGrid.insertAdjacentHTML('beforeend', `<div style="grid-column: 1/-1; text-align: center; padding: 12px 16px; color: var(--text-dim); font-size: 0.78rem;">Showing ${previewAssets.length} lightweight previews. ${hiddenPreviewCount} additional heavy assets were skipped to keep the page fast.</div>`);
        }
        initializeAssetPreviews();

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

        // Save Config -> POST to API & store final_zip_name in state
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

                    saveConfigBtn.innerText          = '<i class="fa-solid fa-check"></i> Config Saved';
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
            const isRenaming = state.renamingOutputIndex === idx;
            const isSaving = state.renameSavingIndex === idx;
            const sourceName = escapeHtml(src.name || 'Unknown Source');
            const outputName = escapeHtml(out.name || 'Untitled Output');
            const nameParts = getFilenameParts(out.name || '');
            const renameDraft = escapeHtml(state.renameDraftValue || nameParts.base);
            const extension = escapeHtml(nameParts.extension);
            
            html += `
                <div style="display: flex; flex-direction: column; gap: 12px; padding: 20px 24px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <span class="badge" style="font-size: 0.6rem; padding: 4px 10px; opacity: 0.8;">${(src.name || '').split('.').pop().toUpperCase()}</span>
                            <div>
                                <p style="font-weight: 800; font-size: 1rem; margin-bottom: 2px;">${sourceName}</p>
                                <p style="font-size: 0.75rem; color: var(--text-dim); font-weight: 600;">${formatFileSize(src.size)} • ${src.asset_count || 0} assets</p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            ${authorTags.map(t => `<span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-dim); border: 1px solid var(--border-color); font-size: 0.65rem;">${escapeHtml(t)}</span>`).join('') || '<span class="badge" style="background: rgba(239, 68, 68, 0.05); color: #ef4444; border-color: rgba(239, 68, 68, 0.1); font-size: 0.65rem;">No Tags</span>'}
                        </div>
                    </div>
                    <div style="background: rgba(0,0,0,0.2); padding: 10px 16px; border-radius: 8px; border-left: 3px solid var(--primary); display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 260px;">
                            <p style="font-size: 0.6rem; text-transform: uppercase; font-weight: 800; color: var(--text-dim); margin-bottom: 4px; letter-spacing: 0.05em;">Final Output Name</p>
                            ${isRenaming ? `
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                        <input
                                            id="renameOutputInput"
                                            type="text"
                                            value="${renameDraft}"
                                            oninput="window.setRenameDraftValue(this.value)"
                                            onkeydown="window.handleRenameKeydown(event, ${idx})"
                                            class="text-input"
                                            ${isSaving ? 'disabled' : ''}
                                            style="height: 44px; min-width: 240px; flex: 1; font-weight: 800;"
                                        >
                                        ${extension ? `<span style="padding: 0 14px; height: 44px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.75rem; font-weight: 800;">.${extension}</span>` : ''}
                                    </div>
                                    ${state.renameErrorMessage ? `<p style="font-size: 0.72rem; color: var(--error); font-weight: 700;">${escapeHtml(state.renameErrorMessage)}</p>` : ''}
                                </div>
                            ` : `
                                <button
                                    type="button"
                                    onclick="window.startRenameOutput(${idx})"
                                    style="background: transparent; border: none; padding: 0; color: var(--primary); font-weight: 800; font-size: 0.8rem; cursor: pointer; text-align: left;"
                                >${outputName}</button>
                            `}
                        </div>
                        ${isRenaming ? `
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <button type="button" class="btn btn-secondary" onclick="window.cancelRenameOutput()" ${isSaving ? 'disabled' : ''} style="padding: 6px 12px; font-size: 0.75rem; display: flex; align-items: center; gap: 6px; opacity: ${isSaving ? '0.65' : '1'};">
                                    Cancel
                                </button>
                                <button type="button" class="btn btn-primary" onclick="window.renameOutputFile(${idx})" ${isSaving ? 'disabled' : ''} style="padding: 6px 14px; font-size: 0.75rem; display: flex; align-items: center; gap: 6px; min-width: 102px;">
                                    ${isSaving ? 'Saving...' : 'Save'}
                                </button>
                            </div>
                        ` : `
                            <button type="button" class="btn btn-secondary" onclick="window.startRenameOutput(${idx})" style="padding: 6px 12px; font-size: 0.75rem; display: flex; align-items: center; gap: 6px;">
                                Rename
                            </button>
                        `}
                    </div>
                </div>
            `;
        });
        
        sourceList.innerHTML = html;
        document.getElementById('sourceCoverageInfo').innerText = 'Ready to review and rename files manually';

        if (state.renamingOutputIndex !== null) {
            requestAnimationFrame(() => {
                const renameInput = document.getElementById('renameOutputInput');
                if (!renameInput) return;

                renameInput.focus();
                renameInput.select();
            });
        }
    }

    window.setRenameDraftValue = function(value) {
        state.renameDraftValue = value;
    };

    window.startRenameOutput = function(index) {
        const output = state.outputs?.[index];
        if (!output || state.renameSavingIndex !== null) return;

        state.renamingOutputIndex = index;
        state.renameDraftValue = getFilenameParts(output.name).base;
        state.renameErrorMessage = '';
        renderReedit();
    };

    window.cancelRenameOutput = function() {
        if (state.renameSavingIndex !== null) return;

        state.renamingOutputIndex = null;
        state.renameDraftValue = '';
        state.renameErrorMessage = '';
        renderReedit();
    };

    window.handleRenameKeydown = function(event, index) {
        if (event.key === 'Enter') {
            event.preventDefault();
            window.renameOutputFile(index);
        } else if (event.key === 'Escape') {
            event.preventDefault();
            window.cancelRenameOutput();
        }
    };

    window.renameOutputFile = async function(index) {
        const output = state.outputs?.[index];
        if (!output || state.renameSavingIndex !== null) return;

        const parts = getFilenameParts(output.name);
        const renameInput = document.getElementById('renameOutputInput');
        const draftValue = (renameInput?.value ?? state.renameDraftValue ?? '').trim();

        if (!draftValue) {
            state.renameErrorMessage = 'Please enter a file name.';
            renderReedit();
            return;
        }

        if (/[\\\/]/.test(draftValue)) {
            state.renameErrorMessage = 'File name cannot contain slashes.';
            renderReedit();
            return;
        }

        const newName = parts.extension ? `${draftValue}.${parts.extension}` : draftValue;
        if (newName === output.name) {
            window.cancelRenameOutput();
            return;
        }

        const previousOutputs = state.outputs.map(item => ({ ...item }));
        state.renameSavingIndex = index;
        state.renameErrorMessage = '';
        state.renameDraftValue = draftValue;
        state.outputs = state.outputs.map((item, itemIndex) => itemIndex === index ? { ...item, name: newName } : item);
        renderReedit();
        if (state.status === 'completed') {
            renderOutputs();
        }

        try {
            const formData = new FormData();
            formData.append('old_name', output.name);
            formData.append('new_name', newName);
            if (window.oauthToken) {
                formData.append('drive_token', window.oauthToken);
            }
            
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
            state.driveStorage = data.drive_storage || state.driveStorage;
            state.renamingOutputIndex = null;
            state.renameSavingIndex = null;
            state.renameDraftValue = '';
            state.renameErrorMessage = '';
            renderReedit();
            if (state.status === 'completed') {
                renderOutputs();
            }
        } catch (err) {
            state.outputs = previousOutputs;
            state.renameSavingIndex = null;
            state.renamingOutputIndex = index;
            state.renameDraftValue = draftValue;
            state.renameErrorMessage = err instanceof Error ? err.message : 'Rename failed';
            renderReedit();
            if (state.status === 'completed') {
                renderOutputs();
            }
        }
    };

    function renderOutputs() {
        const outCont = document.getElementById('outputContainer');
        const resolvedApiBase = getResolvedApiBase();
        const archiveName = state.bundle?.name || (state.finalZipName || state.storeName ? `${state.finalZipName || state.storeName}.zip` : '');
        const driveStatusText = getDriveStorageStatusText();
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

        const driveInfoHtml = state.driveStorage ? `
            <div style="margin-bottom: 24px; padding: 16px 20px; background: rgba(66,133,244,0.08); border: 1px solid rgba(66,133,244,0.22); border-radius: 12px; display: flex; justify-content: space-between; align-items: center; gap: 16px;">
                <div style="flex: 1;">
                    <p style="font-size: 0.6rem; font-weight: 800; color: #8ab4f8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px;">Google Drive Storage</p>
                    <p style="font-size: 0.82rem; color: var(--text-main); margin: 0;">${driveStatusText || 'Drive storage is available for this job.'}</p>
                </div>
                ${state.driveStorage.job_folder_url ? `<a href="${state.driveStorage.job_folder_url}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="white-space: nowrap;">Open Drive Folder</a>` : ''}
            </div>` : '';

        // Show configured filename info
        const infoHtml = archiveName ? `
            <div style="margin-bottom: 24px; padding: 16px 20px; background: rgba(249,115,22,0.06); border: 1px solid rgba(249,115,22,0.2); border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 1.4rem;"><i class="fa-solid fa-box"></i></span>
                <div style="flex: 1;">
                    <p style="font-size: 0.6rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;">Output Archive Name</p>
                    <input id="finalDownloadNameIn" type="text" value="${archiveName}" class="text-input" style="background: rgba(0,0,0,0.3); height: 42px; font-weight: 800; width: 100%; border: 1px solid rgba(255,255,255,0.1); margin-top: 8px;">
                </div>
            </div>` : '';

        outCont.innerHTML = driveInfoHtml + infoHtml + `
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
        stopStageActivity();
        state.progressMeta = null;
        state.lastBackendProgressAt = 0;
        state.uploadedFileCount = state.files.length;
        state.uploadedFileNames = state.files.map(file => file.name);
        state.status = 'uploading';
        setProgressDisplay(1);
        updateView();

        try {
            let response;

            if (OWNER_MANAGED_DRIVE) {
                try {
                    response = await uploadViaManagedDrive(state.files);
                } catch (driveError) {
                    console.warn('Managed Google Drive upload failed, falling back to direct upload.', driveError);
                    if (elements.stageTitle) elements.stageTitle.innerText = "Syncing to Engine...";
                    if (elements.stageMessage) elements.stageMessage.innerText = "Managed Drive upload was unavailable. Falling back to direct upload.";
                    response = await uploadWithChunks(state.files);
                }
            } else {
                response = await uploadWithChunks(state.files);
            }

            if (!response.ok) {
                state.status = 'error';
                elements.errorMessage.innerText = await readErrorMessage(response, 'Upload failed');
                updateView();
                return;
            }

            const data = await response.json();
            state.jobId = data.job_id;
            state.driveStorage = data.drive_storage || state.driveStorage;
            state.progressMeta = data.progress_meta || null;
            state.status = 'uploaded';
            updateView();
        } catch (error) {
            stopStageActivity();
            state.status = 'error';
            elements.errorMessage.innerText = error instanceof Error ? error.message : 'Upload request failed';
            updateView();
        }
    }

    elements.launchBtn.onclick = handleUpload;

    async function handleLinkUpload() {
        const url = document.getElementById('linkUploadInput').value.trim();
        if (!url) return alert('Please enter a secure file link (Google Drive or direct URL)');

        stopStageActivity();
        state.progressMeta = null;
        state.lastBackendProgressAt = 0;
        state.uploadedFileCount = 1;
        state.uploadedFileNames = ['Remote file'];
        state.status = 'uploading';
        setProgressDisplay(1);
        updateView();
        
        // Show cloud fetch specific messaging
        if (elements.stageTitle) elements.stageTitle.innerText = "Fetching Cloud File...";
        if (elements.stageMessage) elements.stageMessage.innerText = "Requesting remote node to secure the asset. This may take a moment depending on file size.";

        try {
            const response = await apiFetch('/upload-url', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    url,
                    token: window.oauthToken || '',
                    drive_token: window.oauthToken || ''
                })
            });

            if (!response.ok) {
                clearInterval(pollInterval);
                state.status = 'error';
                elements.errorMessage.innerText = await readErrorMessage(response, 'Cloud import failed');
                updateView();
                return;
            }

            const data = await response.json();
            state.jobId = data.job_id;
            state.driveStorage = data.drive_storage || state.driveStorage;
            state.progressMeta = data.progress_meta || null;
            state.status = 'uploaded';
            updateView();
        } catch (error) {
            clearInterval(pollInterval);
            stopStageActivity();
            state.status = 'error';
            elements.errorMessage.innerText = error instanceof Error ? error.message : 'Cloud fetch request failed';
            updateView();
        }
    }

    const linkUploadBtn = document.getElementById('linkUploadBtn');
    if (linkUploadBtn) linkUploadBtn.onclick = handleLinkUpload;

    async function handleScan() {
        stopStageActivity();
        state.progressMeta = {
            phase: 'scan',
            total_files: state.uploadedFileCount || state.files.length || 1,
            current_file_index: 1,
            current_file_name: state.uploadedFileNames[0] || 'Package',
            assets_found: 0,
            detected_authors: 0,
            elapsed_seconds: 0
        };
        state.status = 'scanning';
        setProgressDisplay(3);
        updateView();
        startStageActivity('scanning');
        try {
            const formData = new FormData();
            formData.append('blocked_keywords', '[]');
            pollStatus();
            const resp = await apiFetch(`/jobs/${state.jobId}/scan`, { method: 'POST', body: formData });
            if (!resp.ok) {
                clearInterval(pollInterval);
                throw new Error(await readErrorMessage(resp, "Branding scan failed to start"));
            }

            const data = await resp.json();
            clearInterval(pollInterval);
            stopStageActivity();
            state.lastBackendProgressAt = Date.now();
            state.progressMeta = data.progress_meta || null;

            if (data.status === 'scanned') {
                setProgressDisplay(100);
                state.status = 'scanned';
                state.manifest = data.manifest;
                state.driveStorage = data.drive_storage || state.driveStorage;
                updateView();

                if (document.getElementById('globalAutoProcess')?.checked) {
                    setTimeout(() => handleRebrand(), 500);
                }
            }
        } catch (e) {
            clearInterval(pollInterval);
            stopStageActivity();
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

        stopStageActivity();
        state.progressMeta = {
            phase: 'rebrand',
            total_files: state.manifest?.source_files?.length || state.uploadedFileCount || 1,
            current_file_index: 1,
            current_file_name: state.manifest?.source_files?.[0]?.name || state.uploadedFileNames[0] || 'Package',
            elapsed_seconds: 0,
            action: 'Rebrand + Zip'
        };
        state.status = 'processing';
        setProgressDisplay(8);
        updateView();
        startStageActivity('processing');
        
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
            if (window.oauthToken) {
                formData.append('drive_token', window.oauthToken);
            }

            pollStatus();
            const resp = await apiFetch(`/jobs/${state.jobId}/rebrand`, { method: 'POST', body: formData });
            if (!resp.ok) {
                clearInterval(pollInterval);
                throw new Error(await readErrorMessage(resp, "Repackaging process failed"));
            }

            const data = await resp.json();
            clearInterval(pollInterval);
            stopStageActivity();
            state.lastBackendProgressAt = Date.now();
            state.progressMeta = data.progress_meta || null;

            if (data.status === 'completed') {
                setProgressDisplay(100);
                const isAuto = document.getElementById('globalAutoProcess')?.checked || document.getElementById('autoProcessCheckbox')?.checked;
                state.status = isAuto ? 'completed' : 'reedit';
                state.outputs = data.outputs;
                state.bundle = data.bundle;
                state.storeName = data.store_name || state.storeName || '';
                state.finalZipName = data.final_zip_name || state.finalZipName || (data.bundle?.name ? data.bundle.name.replace(/\.zip$/i, '') : '');
                state.driveStorage = data.drive_storage || state.driveStorage;
                updateView();

                if (isAuto) {
                    setTimeout(() => {
                        const dlBtn = document.getElementById('downloadAllBtn');
                        if (dlBtn && dlBtn.href) {
                            window.location.href = dlBtn.href;
                        }
                    }, 1000);
                }
            }
        } catch (e) {
            clearInterval(pollInterval);
            stopStageActivity();
            state.status = 'error';
            elements.errorMessage.innerText = e.message;
            updateView();
        }
    }

    // Removed old saveConfigBtn override

    let pollInterval;
    let pollFailCount = 0;
    function pollStatus() {
        if (!state.jobId) {
            return;
        }

        clearInterval(pollInterval);
        pollFailCount = 0;
        pollInterval = setInterval(async () => {
            try {
                const resp = await apiFetch(`/jobs/${state.jobId}`);
                if (!resp.ok) throw new Error(await readErrorMessage(resp, "Connection lost"));
                const data = await resp.json();
                state.lastBackendProgressAt = Date.now();
                state.progressMeta = data.progress_meta || state.progressMeta;
                setProgressDisplay(data.progress || 0);
                if (data.progress_message) elements.stageMessage.innerText = data.progress_message;

                if (state.status === 'scanning') {
                    updateScanningStats(state.progressMeta);
                } else if (state.status === 'processing') {
                    updateProcessingStats(state.progressMeta);
                }

                if (data.status === 'scanned') {
                    clearInterval(pollInterval);
                    stopStageActivity();
                    if (state.status !== 'scanned') {
                        setProgressDisplay(100);
                        state.status = 'scanned';
                        state.manifest = data.manifest;
                        state.driveStorage = data.drive_storage || state.driveStorage;
                        updateView();
                        
                        if (document.getElementById('globalAutoProcess')?.checked) {
                            setTimeout(() => handleRebrand(), 500);
                        }
                    }
                } else if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    stopStageActivity();
                    if (!['completed', 'reedit'].includes(state.status)) {
                        setProgressDisplay(100);
                        const isAuto = document.getElementById('globalAutoProcess')?.checked || document.getElementById('autoProcessCheckbox')?.checked;
                        state.status = isAuto ? 'completed' : 'reedit';
                        state.outputs = data.outputs;
                        state.bundle = data.bundle;
                        state.storeName = data.store_name || state.storeName || '';
                        state.finalZipName = data.final_zip_name || state.finalZipName || (data.bundle?.name ? data.bundle.name.replace(/\.zip$/i, '') : '');
                        state.driveStorage = data.drive_storage || state.driveStorage;
                        updateView();

                        if (isAuto) {
                            setTimeout(() => {
                                const dlBtn = document.getElementById('downloadAllBtn');
                                if (dlBtn && dlBtn.href) {
                                    window.location.href = dlBtn.href;
                                }
                            }, 1000);
                        }
                    }
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    stopStageActivity();
                    state.status = 'error';
                    elements.errorMessage.innerText = data.error || "Processing failed.";
                    updateView();
                }
            } catch (e) {
                console.error(e);
                pollFailCount = (pollFailCount || 0) + 1;
                if (pollFailCount >= 3) {
                    clearInterval(pollInterval);
                    stopStageActivity();
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
        clearInterval(pollInterval);
        stopStageActivity();
        state = {
            files: [],
            jobId: null,
            status: 'idle',
            manifest: null,
            outputs: [],
            bundle: null,
            storeName: '',
            finalZipName: '',
            driveStorage: null,
            progressValue: 0,
            progressMeta: null,
            uploadedFileCount: 0,
            uploadedFileNames: [],
            lastBackendProgressAt: 0,
            renamingOutputIndex: null,
            renameDraftValue: '',
            renameSavingIndex: null,
            renameErrorMessage: ''
        };
        
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

    // --- Storage Management Logic ---
    async function refreshStorageStats() {
        if (storageStatsInFlight) {
            return;
        }

        if (document.hidden) {
            scheduleStorageStatsRefresh(30000);
            return;
        }

        if (typeof navigator !== 'undefined' && navigator.onLine === false) {
            const offlineNote = document.getElementById('storageUsageNote');
            if (offlineNote) offlineNote.innerText = 'Storage stats paused while offline.';
            scheduleStorageStatsRefresh(45000);
            return;
        }

        storageStatsInFlight = true;
        try {
            const resp = await apiFetch('/storage/stats');
            let data = { used_bytes: 0, total_mb: 2097152, percent: 0, status: 'normal' };
            if (resp.ok) data = await resp.json();
            storageStatsFailCount = 0;

            let usedBytes = data.used_bytes;
            let totalMB = data.total_mb;
            let labelPrefix = data.provider === 'google_drive' ? 'GOOGLE DRIVE' : 'ENGINE STORAGE';

            // In user-managed mode, switch the card to the signed-in Drive quota once OAuth is active
            if (!OWNER_MANAGED_DRIVE && window.oauthToken) {
                try {
                    const gResp = await fetch(`https://www.googleapis.com/drive/v3/about?fields=storageQuota&access_token=${window.oauthToken}`);
                    if (gResp.ok) {
                        const gData = await gResp.json();
                        if (gData.storageQuota) {
                            usedBytes = parseInt(gData.storageQuota.usage);
                            totalMB = parseInt(gData.storageQuota.limit) / (1024 * 1024);
                            labelPrefix = "GOOGLE DRIVE";
                        }
                    }
                } catch (err) {
                    console.error("GDrive Quota Fetch Error:", err);
                }
            }

            const usedGB = usedBytes / (1024 * 1024 * 1024);
            const totalGB = totalMB / 1024;
            
            let usedStr = usedGB >= 1024 ? (usedGB / 1024).toFixed(2) + " TB" : usedGB.toFixed(2) + " GB";
            let totalStr = totalGB >= 1024 ? (totalGB / 1024).toFixed(0) + " TB" : totalGB.toFixed(0) + " GB";
            
            const percent = ((usedBytes / (totalMB * 1024 * 1024)) * 100).toFixed(1);
            
            const label = document.getElementById('storageUsageLabel');
            const bar = document.getElementById('storageBarFill');
            const contextLabel = document.querySelector('.storage-label-text');
            const note = document.getElementById('storageUsageNote');
            const rootLink = document.getElementById('storageRootFolderLink');

            if (data.status === 'error') {
                if (contextLabel) contextLabel.innerText = "GOOGLE DRIVE USAGE";
                if (label) label.innerText = "Unavailable";
                if (note) note.innerText = data.error || 'Google Drive quota could not be loaded.';
                if (rootLink) rootLink.style.display = 'none';
                if (bar) {
                    bar.style.width = "100%";
                    bar.classList.remove('storage-status-normal', 'storage-status-warning');
                    bar.classList.add('storage-status-full');
                }
                return;
            }
            
            if (contextLabel) contextLabel.innerText = labelPrefix + " USAGE";
            if (label) label.innerText = `${usedStr} / ${totalStr} (${percent}%)`;
            if (note) note.innerText = `Remaining: ${formatStorageAmount(data.remaining_bytes || Math.max(0, (totalMB * 1024 * 1024) - usedBytes))}`;
            if (rootLink) {
                if (data.root_folder_url) {
                    rootLink.href = data.root_folder_url;
                    rootLink.style.display = 'inline-flex';
                } else {
                    rootLink.style.display = 'none';
                }
            }
            
            if (bar) {
                bar.style.width = percent + "%";
                bar.classList.remove('storage-status-normal', 'storage-status-warning', 'storage-status-full');
                if (percent >= 95) bar.classList.add('storage-status-full');
                else if (percent >= 80) bar.classList.add('storage-status-warning');
                else bar.classList.add('storage-status-normal');
            }
        } catch (e) {
            storageStatsFailCount += 1;
            const note = document.getElementById('storageUsageNote');
            if (note) {
                note.innerText = storageStatsFailCount >= 3
                    ? 'Storage stats temporarily unavailable. Retrying quietly in the background.'
                    : 'Refreshing storage usage...';
            }
        } finally {
            storageStatsInFlight = false;
            const nextDelay = storageStatsFailCount === 0
                ? 30000
                : Math.min(120000, 30000 + (storageStatsFailCount * 15000));
            scheduleStorageStatsRefresh(nextDelay);
        }
    }

    function scheduleStorageStatsRefresh(delay = 30000) {
        if (storageStatsTimer) {
            clearTimeout(storageStatsTimer);
        }

        storageStatsTimer = window.setTimeout(() => {
            refreshStorageStats();
        }, delay);
    }

    window.addEventListener('online', () => {
        storageStatsFailCount = 0;
        refreshStorageStats();
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshStorageStats();
        }
    });

    refreshStorageStats(); 

    updateView();
</script>
@endpush

