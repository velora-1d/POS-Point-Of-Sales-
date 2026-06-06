// Give the service worker access to Firebase Messaging.
importScripts(
    'https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js',
);
importScripts(
    'https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js',
);

// Initialize the Firebase app in the service worker.
firebase.initializeApp({
    apiKey: 'AIzaSyD9nfoQgmtfi-ZQbjp6LU3f0Lj1_D5Qgn4',
    authDomain: 'pos-mentai.firebaseapp.com',
    projectId: 'pos-mentai',
    storageBucket: 'pos-mentai.firebasestorage.app',
    messagingSenderId: '730711731810',
    appId: '1:730711731810:web:ff24acab12589b98922b3b',
});

// Retrieve an instance of Firebase Messaging.
const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    console.log(
        '[firebase-messaging-sw.js] Received background message ',
        payload,
    );

    const notificationTitle = payload.notification?.title || 'Notifikasi Baru';
    const notificationOptions = {
        body: payload.notification?.body || 'Ada pembaruan pesanan/meja.',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        data: payload.data,
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});
