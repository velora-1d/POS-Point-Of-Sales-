import { getAnalytics } from 'firebase/analytics';
import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage } from 'firebase/messaging';

// Your web app's Firebase configuration
const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID,
};

// Initialize Firebase
export const app = initializeApp(firebaseConfig);
export const analytics =
    typeof window !== 'undefined' ? getAnalytics(app) : null;
export const messaging =
    typeof window !== 'undefined' ? getMessaging(app) : null;

export { onMessage };

export async function requestNotificationPermission() {
    if (typeof window === 'undefined' || !('Notification' in window)) {
        console.warn('Browser tidak mendukung notifikasi.');
        return null;
    }

    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
        console.log('Izin notifikasi diberikan.');
        return getFCMToken();
    } else {
        console.warn('Izin notifikasi ditolak.');
        return null;
    }
}

export async function getFCMToken() {
    if (!messaging) return null;
    try {
        const token = await getToken(messaging, {
            vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
        });
        if (token) {
            console.log('FCM Token berhasil didapatkan:', token);
            return token;
        } else {
            console.warn('Token FCM tidak tersedia.');
            return null;
        }
    } catch (error) {
        console.error('Gagal mengambil Token FCM:', error);
        return null;
    }
}
