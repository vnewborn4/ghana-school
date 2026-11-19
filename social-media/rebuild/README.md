# Corrected social package

This directory contains the documentary-first rebuild requested after the first social package was rejected.

## Direction

- Authentic classroom footage and photography fill the frame.
- Typography is concise and subordinate to the students and learning environment.
- Gold is used only as an accent; there are no persistent page frames or heavy opaque cards.
- Narration uses the imported HeyGen `Van voice` only. No avatar appears in the finished videos.
- Speech remains at its generated natural speed; the edit will follow the accepted narration rather than forcing a target duration.

## Render

```powershell
python social-media/rebuild/render.py --static-only
```

Video rendering remains intentionally blocked until the accepted HeyGen narration is saved as `social-media/rebuild/audio/narration.wav` and aligned captions are available.
