<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Model Chapter - Đại diện cho bảng chapters trong database
 * Chứa thông tin chương học thuộc một khóa học
 * Cấu trúc chuẩn:
 * - id: khóa chính
 * - course_id: liên kết với khóa học
 * - title: tên chương
 * - slug: URL thân thiện (duy nhất, tự động tạo từ title)
 * - description: mô tả chương
 * - order: thứ tự hiển thị
 * - status: trạng thái (draft, published)
 * - is_free: chương miễn phí
 * - thumbnail: ảnh đại diện
 */
class Chapter extends Model
{
    use HasFactory;

    /**
     * Các trường có thể được gán giá trị hàng loạt (mass assignment)
     */
    protected $fillable = [
        'course_id',   // ID của khóa học cha
        'title',       // Tiêu đề chương học
        'slug',        // URL thân thiện (sẽ tự tạo nếu để trống)
        'description', // Mô tả chương học
        'order',       // Thứ tự hiển thị
        'type',        // Loại chương: vocabulary, grammar, listening, speaking
        'status',      // Trạng thái (draft, published)
        'thumbnail',   // Ảnh đại diện chương
    ];

    /**
     * Các kiểu dữ liệu được cast tự động
     */
    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Các loại chương học
     */
    const TYPE_VOCABULARY = 'vocabulary';
    const TYPE_GRAMMAR = 'grammar';
    const TYPE_LISTENING = 'listening';
    const TYPE_SPEAKING = 'speaking';

    /**
     * Lấy danh sách các loại chương học
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_VOCABULARY,
            self::TYPE_GRAMMAR,
            self::TYPE_LISTENING,
            self::TYPE_SPEAKING,
        ];
    }

    /**
     * Lấy nhãn hiển thị cho loại chương
     */
    public static function getTypeLabel(string $type): string
    {
        $labels = [
            'vocabulary' => 'Từ Vựng',
            'grammar' => 'Ngữ Pháp',
            'listening' => 'Luyện Nghe',
            'speaking' => 'Luyện Nói',
        ];
        return $labels[$type] ?? $type;
    }

    /**
     * Các giá trị mặc định khi tạo mới
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * Boot method - Tự động tạo slug khi tạo mới hoặc cập nhật title
     */
    protected static function boot()
    {
        parent::boot();

        // Tự động tạo slug từ title khi tạo mới
        static::creating(function ($chapter) {
            if (empty($chapter->slug) && !empty($chapter->title)) {
                $baseSlug = Str::slug($chapter->title);
                $slug = $baseSlug;
                $counter = 1;
                
                // Kiểm tra slug đã tồn tại chưa, nếu có thì thêm suffix
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $chapter->slug = $slug;
            }
        });

        // Tự động cập nhật slug khi title thay đổi
        static::updating(function ($chapter) {
            if ($chapter->isDirty('title') && !$chapter->isDirty('slug')) {
                $baseSlug = Str::slug($chapter->title);
                $slug = $baseSlug;
                $counter = 1;
                
                // Kiểm tra slug đã tồn tại chưa (trừ chính nó)
                while (static::where('slug', $slug)->where('id', '!=', $chapter->id)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $chapter->slug = $slug;
            }
        });
    }

    /**
     * Định nghĩa mối quan hệ: Chapter thuộc về một Course
     * 
     * @return BelongsTo
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Định nghĩa mối quan hệ: Một Chapter có nhiều Lessons
     * Sắp xếp theo thứ tự order
     * Khi xóa Chapter, tất cả Lessons liên quan sẽ được xóa (cascade)
     * 
     * @return HasMany
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    /**
     * Accessor: Kiểm tra chương đã publish chưa
     * 
     * @return bool
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Scope: Lọc chương đã publish
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope: Sắp xếp theo thứ tự
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
