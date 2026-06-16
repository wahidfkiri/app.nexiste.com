use tauri::{Emitter, Manager};

#[tauri::command]
fn desktop_context() -> serde_json::Value {
    serde_json::json!({
        "productName": "Nexus CRM",
        "desktopScheme": "nexuscrm",
        "desktopMode": "tauri-remote-wrapper",
        "version": env!("CARGO_PKG_VERSION"),
    })
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    let mut builder = tauri::Builder::default();

    #[cfg(desktop)]
    {
        builder = builder.plugin(tauri_plugin_single_instance::init(|app, args, cwd| {
            let _ = app.emit(
                "nexus://single-instance",
                serde_json::json!({
                    "args": args,
                    "cwd": cwd,
                }),
            );

            if let Some(window) = app.get_webview_window("main") {
                let _ = window.show();
                let _ = window.unminimize();
                let _ = window.set_focus();
            }
        }));
    }

    builder
        .plugin(tauri_plugin_opener::init())
        .plugin(tauri_plugin_notification::init())
        .plugin(tauri_plugin_deep_link::init())
        .setup(|app| {
            #[cfg(any(windows, target_os = "linux"))]
            {
                use tauri_plugin_deep_link::DeepLinkExt;

                let _ = app.deep_link().register_all();
            }

            Ok(())
        })
        .invoke_handler(tauri::generate_handler![desktop_context])
        .run(tauri::generate_context!())
        .expect("error while running Nexus CRM desktop");
}
