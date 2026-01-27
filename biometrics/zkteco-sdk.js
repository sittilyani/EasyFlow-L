// zkteco-sdk.js
class ZKTecoSDK {
    constructor() {
        this.device = null;
        this.isConnected = false;
        this.onFingerprintCaptured = null;
    }

    async initialize() {
        try {
            // Load ZKTeco WebSocket SDK or ActiveX control
            if (typeof ZKFingerReader !== 'undefined') {
                this.device = new ZKFingerReader();
                const result = await this.device.Init();
                if (result === 0) {
                    this.isConnected = true;

                    // Set callback for fingerprint capture
                    this.device.OnImageReceived = (imageData) => {
                        this.handleFingerprintImage(imageData);
                    };

                    this.device.OnFeatureInfo = (templateData) => {
                        this.handleTemplateData(templateData);
                    };

                    return true;
                }
            }
            return false;
        } catch (error) {
            console.error('ZKTeco SDK error:', error);
            throw error;
        }
    }

    async startCapture() {
        if (!this.isConnected) {
            throw new Error('Device not connected');
        }

        // Start fingerprint capture
        const result = await this.device.StartCapture();
        if (result !== 0) {
            throw new Error('Capture failed with code: ' + result);
        }
    }

    stopCapture() {
        if (this.device && this.isConnected) {
            this.device.StopCapture();
        }
    }

    handleFingerprintImage(imageData) {
        // Convert image data to base64
        const base64Image = this.arrayBufferToBase64(imageData);

        if (this.onFingerprintCaptured) {
            this.onFingerprintCaptured({
                image: base64Image,
                type: 'image',
                timestamp: new Date().toISOString()
            });
        }
    }

    handleTemplateData(templateData) {
        if (this.onFingerprintCaptured) {
            this.onFingerprintCaptured({
                template: this.arrayBufferToBase64(templateData),
                type: 'template',
                timestamp: new Date().toISOString()
            });
        }
    }

    arrayBufferToBase64(buffer) {
        let binary = '';
        const bytes = new Uint8Array(buffer);
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    }

    getDeviceInfo() {
        if (this.device && this.isConnected) {
            return {
                vendor: 'ZKTeco',
                model: this.device.GetDeviceName(),
                serial: this.device.GetSerialNumber(),
                firmware: this.device.GetFirmwareVersion()
            };
        }
        return null;
    }
}
