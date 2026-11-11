import { test, expect } from './fixtures';
import { startMockSseServer } from './mock-sse-server.js';

let mockServer;

test.beforeAll(async () => {
  mockServer = await startMockSseServer(8089);
});

test.afterAll(async () => {
  if (mockServer) {
    await mockServer.close();
  }
});

test.describe('E2E: Chat Experience & Background Streaming', () => {

  test('Escenario 1: Reset sin modal y borrador preservado', async ({ authenticatedPage: page }) => {
    await page.goto('/chat');
    const promptInput = page.locator('#promptInput');
    const btnNewChat = page.locator('#btnNewChat');

    // 1. Redactar borrador sin enviar
    await promptInput.fill('Mi borrador de prueba para el nuevo chat');

    // 2. Hacer clic en "+ Nueva Conversación"
    await btnNewChat.click();

    // 3. Verificar que no se levantó ningún diálogo Swal
    await expect(page.locator('.swal2-container')).toHaveCount(0);

    // 4. Verificar que el texto en promptInput se conservó intacto
    await expect(promptInput).toHaveValue('Mi borrador de prueba para el nuevo chat');
    await expect(page).toHaveURL(/\/chat$/);
  });

  test('Escenario 2: Aislamiento entre dos sesiones simultáneas sin mezcla de tokens', async ({ authenticatedPage: page }) => {
    // Interceptar /chat/stream y dirigir hacia el mock server
    await page.route('**/chat/stream', async (route) => {
      const response = await fetch('http://localhost:8089/chat/stream', {
        method: route.request().method(),
        headers: route.request().headers(),
        body: route.request().postData()
      });
      const body = await response.text();
      await route.fulfill({
        status: response.status,
        headers: { 'Content-Type': 'text/event-stream' },
        body: body
      });
    });

    await page.goto('/chat');
    await page.waitForFunction(() => typeof window.loadConversationSession === 'function');
    const promptInput = page.locator('#promptInput');
    const btnNewChat = page.locator('#btnNewChat');

    // 1. Enviar en Sesión A
    await promptInput.fill('Pregunta Alfa');
    await page.locator('#btnSendMessage').click();
    await expect(page.locator('#chatMessagesContainer')).toContainText('Pregunta Alfa');

    // 2. Cambiar inmediatamente a Nueva Conversación
    await page.waitForTimeout(200);
    await btnNewChat.click();

    // 3. Enviar en Sesión B
    await promptInput.fill('Pregunta Beta');
    await page.locator('#btnSendMessage').click();

    // 4. Verificar que en la vista activa (Sesión B) aparecen tokens de Beta y NINGUNO de Alfa
    await expect(page.locator('#chatMessagesContainer')).toContainText('Pregunta Beta');
    await expect(page.locator('#chatMessagesContainer')).not.toContainText('Pregunta Alfa');
  });

  test('Escenario 3: Bloqueo del 4.º stream concurrente', async ({ authenticatedPage: page }) => {
    await page.goto('/chat');
    await page.waitForFunction(() => typeof window.ActiveJobStore !== 'undefined');

    // Simular que ya hay 3 streams activos de otras sesiones en ActiveJobStore
    await page.evaluate(() => {
      window.ActiveJobStore.set('sess_other_1', { sessionId: 'sess_other_1', status: 'streaming' });
      window.ActiveJobStore.set('sess_other_2', { sessionId: 'sess_other_2', status: 'streaming' });
      window.ActiveJobStore.set('sess_other_3', { sessionId: 'sess_other_3', status: 'streaming' });
    });

    const promptInput = page.locator('#promptInput');
    await promptInput.fill('Intentando cuarta consulta');
    await page.locator('#btnSendMessage').click();

    // Verificar notificación de advertencia sobre límite de concurrencia
    await expect(page.locator('.toast-warning')).toBeVisible();
    await expect(page.locator('.toast-warning')).toContainText('Hay 3 consultas procesándose en segundo plano');
  });

  test('Escenario 4: Reconexión en vivo a sesión en streaming', async ({ authenticatedPage: page }) => {
    await page.goto('/chat');
    await page.waitForFunction(() => typeof window.loadConversationSession === 'function');

    // Simular un job en streaming en ActiveJobStore
    await page.evaluate(() => {
      window.ActiveJobStore.set('sess_reconnect_123', {
        streamId: 'stream_rec_123',
        sessionId: 'sess_reconnect_123',
        prompt: 'Consulta de Reconexión',
        sessionTitle: 'Consulta de Reconexión',
        status: 'streaming',
        rawMarkdown: 'Texto acumulado en segundo plano hasta ahora',
        hasUnread: false
      });
    });

    // Abrir esa sesión llamando a loadConversationSession
    await page.evaluate(() => {
      window.loadConversationSession('sess_reconnect_123');
    });

    // Verificar snapshot síncrono acoplado de inmediato
    await expect(page.locator('#chatMessagesContainer')).toContainText('Consulta de Reconexión');
    await expect(page.locator('#chatMessagesContainer')).toContainText('Texto acumulado en segundo plano hasta ahora');
  });

  test('Escenario 5: Error en background muestra badge y tarjeta al abrir', async ({ authenticatedPage: page }) => {
    await page.goto('/chat');
    await page.waitForFunction(() => typeof window.loadConversationSession === 'function');

    // Simular un job fallido retenido en segundo plano
    await page.evaluate(() => {
      window.ActiveJobStore.set('sess_error_123', {
        streamId: 'stream_err_123',
        sessionId: 'sess_error_123',
        prompt: 'Pregunta que falló',
        sessionTitle: 'Pregunta que falló',
        status: 'error',
        errorDetail: 'Fallo simulado de conexión con el backend',
        hasUnread: true
      });
      window.refreshUserSessionsSidebar();
    });

    // Abrir la sesión fallida
    await page.evaluate(() => {
      window.loadConversationSession('sess_error_123');
    });

    // Verificar renderizado de tarjeta de error con botón reintentar
    await expect(page.locator('#chatMessagesContainer')).toContainText('Error al Procesar la Consulta');
    await expect(page.locator('#chatMessagesContainer')).toContainText('Fallo simulado de conexión con el backend');
    await expect(page.locator('.btn-retry-error')).toBeVisible();
  });

  test('Escenario 6: Detección de sesión interrumpida y reintento', async ({ authenticatedPage: page }) => {
    await page.goto('/chat');
    await page.waitForFunction(() => typeof window.loadConversationSession === 'function');

    // Simular que la sesión 'sess_interrupted_999' estaba en vuelo en sessionStorage
    await page.evaluate(() => {
      sessionStorage.setItem('inflightSessionIds', JSON.stringify(['sess_interrupted_999']));
    });

    // Simular respuesta de /chat/sessions/sess_interrupted_999 con un último mensaje de usuario huérfano
    await page.route('**/chat/sessions/sess_interrupted_999', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            id: 'sess_interrupted_999',
            title: 'Consulta Interrumpida',
            messages: [
              { id: 'msg_u_orphan_1', role: 'user', content: '¿Cuáles son los productos sin stock?' }
            ]
          }
        })
      });
    });

    // Cargar la sesión
    await page.evaluate(() => {
      window.loadConversationSession('sess_interrupted_999');
    });

    // Verificar que se renderiza la tarjeta de consulta incompleta con el botón Reintentar
    await expect(page.locator('#chatMessagesContainer')).toContainText('Consulta Incompleta o Interrumpida');
    const retryBtn = page.locator('.btn-retry-chat');
    await expect(retryBtn).toBeVisible();
    await expect(retryBtn).toHaveAttribute('data-message-id', 'msg_u_orphan_1');
  });

});
