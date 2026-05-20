import io
import base64
try:
    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt
except Exception:
    plt = None

# Function to generate pie chart and return base64
def generate_pie_chart(labels, sizes, title):
    if plt is None:
        # matplotlib not available: return a tiny transparent PNG as a placeholder (base64)
        placeholder_base64 = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/5+hHgAFgwJ/l8sF5QAAAABJRU5ErkJggg=="
        return placeholder_base64
    fig, ax = plt.subplots()
    ax.pie(sizes, labels=labels, autopct='%1.1f%%', startangle=90)
    ax.axis('equal')
    ax.set_title(title)
    buf = io.BytesIO()
    plt.savefig(buf, format='png')
    buf.seek(0)
    plt.close(fig)
    return base64.b64encode(buf.read()).decode('utf-8')

# Data for each chart from the survey

# Chart 1: Använder du ibland engelska ord när du pratar med kompisar?
labels1 = ['Ja', 'Ofta', 'Ibland', 'Aldrig']
sizes1 = [61.5, 23.1, 7.7, 7.7]
title1 = 'Använder du ibland engelska ord när du pratar med kompisar?'
base64_1 = generate_pie_chart(labels1, sizes1, title1)
print("Chart1:", base64_1)

# Chart 2: I vilka situationer använder du engelska ord mest?
labels2 = ['När jag spelar', 'På sociala medier', 'I skolan', 'Bland kompisar']
sizes2 = [38.5, 23.1, 15.4, 23.1]
title2 = 'I vilka situationer använder du engelska ord mest?'
base64_2 = generate_pie_chart(labels2, sizes2, title2)
print("Chart2:", base64_2)

# Chart 3: Tycker du att engelskan påverkar svenskan för mycket?
labels3 = ['Ja', 'Nej', 'Vet ej']
sizes3 = [30.8, 38.5, 30.8]
title3 = 'Tycker du att engelskan påverkar svenskan för mycket?'
base64_3 = generate_pie_chart(labels3, sizes3, title3)
print("Chart3:", base64_3)

# Chart 4: Försöker du ibland ”försvenska” engelska ord?
labels4 = ['Ja', 'Nej', 'Ibland']
sizes4 = [38.5, 23.1, 38.5]
title4 = 'Försöker du ibland ”försvenska” engelska ord?'
base64_4 = generate_pie_chart(labels4, sizes4, title4)
print("Chart4:", base64_4)

# Chart 5: Tycker du det är lättare att uttrycka dig på engelska än på svenska i vissa situationer?
labels5 = ['Ja', 'Nej', 'Ofta', 'Ibland']
sizes5 = [46.2, 7.7, 38.5, 7.7]
title5 = 'Tycker du det är lättare att uttrycka dig på engelska än på svenska i vissa situationer?'
base64_5 = generate_pie_chart(labels5, sizes5, title5)
print("Chart5:", base64_5)

# Chart 6: Har du någon gång märkt att du använder engelska ord utan att tänka på det?
labels6 = ['Ja', 'Nej', 'Ibland']
sizes6 = [76.9, 15.4, 7.7]
title6 = 'Har du någon gång märkt att du använder engelska ord utan att tänka på det?'
base64_6 = generate_pie_chart(labels6, sizes6, title6)
print("Chart6:", base64_6)

# Chart 7: Tycker du att svenska språket kan/kommer förändras mycket på grund av engelskan inom 20 år?
labels7 = ['Ja', 'Nej', 'Vet ej']
sizes7 = [38.5, 23.1, 38.5]
title7 = 'Tycker du att svenska språket kan/kommer förändras mycket på grund av engelskan inom 20 år?'
base64_7 = generate_pie_chart(labels7, sizes7, title7)
print("Chart7:", base64_7)

# Chart 8: Om du hör någon använda mycket engelska, vad tänker du då?
labels8 = ['Det låter coolt', 'Det låter konstigt', 'Spelar ingen roll', 'Annat']
sizes8 = [53.8, 23.1, 15.4, 7.7]
title8 = 'Om du hör någon använda mycket engelska, vad tänker du då?'
base64_8 = generate_pie_chart(labels8, sizes8, title8)
print("Chart8:", base64_8)

# Chart 9: Tycker du att det är viktigt att bevara ett ”rent” svenska utan engelska ord?
labels9 = ['Ja', 'Nej', 'Vet ej']
sizes9 = [46.2, 30.8, 23.1]
title9 = 'Tycker du att det är viktigt att bevara ett ”rent” svenska utan engelska ord?'
base64_9 = generate_pie_chart(labels9, sizes9, title9)
print("Chart9:", base64_9)